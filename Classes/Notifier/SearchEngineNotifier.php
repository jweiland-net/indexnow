<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\IndexNow\Notifier;

use GuzzleHttp\Exception\ClientException;
use JWeiland\IndexNow\Configuration\Exception\ApiKeyNotAvailableException;
use JWeiland\IndexNow\Configuration\ExtConf;
use JWeiland\IndexNow\Domain\Model\Stack;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;

/**
 * Service to communicate with the search engine
 */
class SearchEngineNotifier
{
    /**
     * In batch mode, there is a limit of 10,000 URLs per POST request.
     */
    private const MAX_URLS_EACH_REQUEST = 10000;

    public function __construct(
        protected RequestFactory $requestFactory,
        protected ExtConf $extConf,
        protected LoggerInterface $logger,
    ) {}

    /**
     * @param \SplObjectStorage<Stack> $stackStorage
     */
    public function notify(\SplObjectStorage $stackStorage): bool
    {
        if ($stackStorage->count() === 0) {
            $this->logger->info('No URL records available, nothing sent.');
            return false;
        }

        foreach ($this->groupStackByHost($stackStorage) as $host => $stacksGroupedByHost) {
            $numberOfGroupedStacks = count($stacksGroupedByHost);
            $this->logger->info(sprintf('Preparing batch for %s with %d URL(s)', $host, $numberOfGroupedStacks));

            if (
                ($this->extConf->isNotifyBatchMode() && $numberOfGroupedStacks < 2)
                || !$this->extConf->isNotifyBatchMode()
            ) {
                foreach ($stacksGroupedByHost as $stack) {
                    $this->notifySingleStack($stack);
                }
                continue;
            }

            foreach (array_chunk($stacksGroupedByHost, self::MAX_URLS_EACH_REQUEST) as $chunkedStacksGroupedByHost) {
                $this->notifyGroupedStacks($chunkedStacksGroupedByHost, $host);
            }
        }

        return true;
    }

    protected function notifySingleStack(Stack $stack): bool
    {
        $isValidRequest = false;

        if ($stack->hasValidUrl()) {
            try {
                $response = $this->requestFactory->request($this->getUrlForSingleNotification($stack));
                $statusCode = $response->getStatusCode();

                if ($statusCode === 202) {
                    $this->logger->info('IndexNow received URL, but IndexNow key validation is still pending');
                    $isValidRequest = true;
                } elseif ($statusCode !== 200) {
                    $this->logger->warning(
                        sprintf(
                            'Request to indexnow.org results in StatusCode %d with message: %s',
                            $statusCode,
                            $response->getBody(),
                        ),
                    );
                } else {
                    $isValidRequest = true;
                }
            } catch (ClientException $e) {
                $this->logger->error(
                    sprintf(
                        'Request to indexnow.org results in Error: %s',
                        GeneralUtility::quoteJSvalue($e->getMessage()),
                    ),
                );
            }
        }

        return $isValidRequest;
    }

    /**
     * @throws ApiKeyNotAvailableException
     */
    protected function getUrlForSingleNotification(Stack $stack): string
    {
        // Do not surround with try-catch. It should break in scheduler/cli
        // if no API key is available to inform the user visually
        $uri = new Uri($this->extConf->getSearchEngineEndpoint());
        $uri = $uri->withQuery(HttpUtility::buildQueryString([
            'url' => $stack->getUrl(),
            'key' => $this->extConf->getApiKey(),
        ]));

        return (string)$uri;
    }

    protected function notifyGroupedStacks(array $groupedStacks, string $host): bool
    {
        try {
            $postData = [
                'host' => $host,
                'key' => $this->extConf->getApiKey(),
                'keyLocation' => 'https://' . $host . '/' . $this->extConf->getApiKey() . '.txt',
                'urlList' => $this->getUrlsFromGroupedStacks($groupedStacks),
            ];
        } catch (ApiKeyNotAvailableException) {
            return false;
        }

        try {
            $response = $this->requestFactory->request(
                $this->extConf->getSearchEngineEndpoint(),
                'POST',
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'body' => json_encode($postData),
                ],
            );

            $statusCode = $response->getStatusCode();

            if (in_array($statusCode, [200, 202], true)) {
                $this->logger->info(sprintf(
                    'Batch IndexNow successful for domain %s: %d URLs sent.',
                    $host,
                    count($groupedStacks),
                ));
                return true;
            } else {
                $this->logger->warning(sprintf(
                    'Batch IndexNow failed for domain %s with status %d and message: %s',
                    $host,
                    $statusCode,
                    $response->getBody(),
                ));
            }
        } catch (\Throwable $e) {
            $this->logger->error('Batch IndexNow error for domain ' . $host . ': ' . $e->getMessage());
        }

        return false;
    }

    /**
     * @param Stack[] $groupedStacks
     */
    protected function getUrlsFromGroupedStacks(array $groupedStacks): array
    {
        $urls = [];

        foreach ($groupedStacks as $stack) {
            $urls[] = $stack->getUrl();
        }

        return $urls;
    }

    /**
     * @param \SplObjectStorage<Stack> $stackStorage
     * @return array<string, array<Stack>>
     */
    protected function groupStackByHost(\SplObjectStorage $stackStorage): array
    {
        $groupedStacks = [];

        // domains should be grouped by their host to send them in one batch
        foreach ($stackStorage as $stack) {
            $host = $stack->getHost();
            if ($host === '') {
                $this->logger->warning('Skipping URL with invalid host: ' . $stack->getUrl());
                continue;
            }

            $groupedStacks[$host][] = $stack;
        }

        return $groupedStacks;
    }
}
