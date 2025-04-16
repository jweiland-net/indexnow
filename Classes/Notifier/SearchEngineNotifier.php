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
use JWeiland\IndexNow\Configuration\ExtConf;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Service to communicate with the search engine
 */
class SearchEngineNotifier
{
    public function __construct(
        protected RequestFactory $requestFactory,
        protected LoggerInterface $logger,
        protected ExtConf $extConf,
    ) {}

    public function notify(string $url): bool
    {
        $isValidRequest = false;
        if (GeneralUtility::isValidUrl($url)) {
            try {
                $response = $this->requestFactory->request($url);
                $statusCode = $response->getStatusCode();

                if ($statusCode === 202) {
                    $this->logger->info('IndexNow received URL, but IndexNow key validation is still pending');
                    $isValidRequest = true;
                } elseif ($statusCode !== 200) {
                    $this->logger->warning(
                        sprintf(
                            'Request to indexnow.org results in StatusCode %d with message: %s',
                            $statusCode,
                            $response->getBody()
                        )
                    );
                } else {
                    $isValidRequest = true;
                }
            } catch (ClientException $e) {
                $this->logger->error(
                    sprintf(
                        'Request to indexnow.org results in Error: %s',
                        GeneralUtility::quoteJSvalue($e->getMessage())
                    )
                );
            }
        }

        return $isValidRequest;
    }

    public function notifyBatch(array $urls): bool
    {
        if (empty($urls)) {
            return false;
        }

        $groupedUrls = [];
        // domains should be grouped by their host to send them in one batch
        foreach ($urls as $url) {
            $host = parse_url($url, PHP_URL_HOST);
            if (!$host) {
                continue;
            }
            $groupedUrls[$host][] = $url;
        }

        $overallSuccess = false;

        foreach ($groupedUrls as $domain => $domainUrls) {
            $postData = [
                'host' => $domain,
                'key' => $this->extConf->getApiKey(),
                'keyLocation' => 'https://' . $domain . '/' . $this->extConf->getApiKey() . '.txt',
                'urlList' => $domainUrls,
            ];

            try {
                $response = $this->requestFactory->request(
                    'https://www.bing.com/indexnow',
                    'POST',
                    [
                        'headers' => ['Content-Type' => 'application/json'],
                        'body' => json_encode($postData),
                    ]
                );
                $statusCode = $response->getStatusCode();
                if (in_array($statusCode, [200, 202], true)) {
                    $this->logger->info(sprintf('Batch IndexNow successful for domain %s: %d URLs sent.', $domain, count($domainUrls)));
                    $overallSuccess = true;
                } else {
                    $this->logger->warning(sprintf(
                        'Batch IndexNow failed for domain %s with status %d and message: %s',
                        $domain,
                        $statusCode,
                        $response->getBody()
                    ));
                }
            } catch (\Throwable $e) {
                $this->logger->error('Batch IndexNow error for domain ' . $domain . ': ' . $e->getMessage());
            }
        }

        return $overallSuccess;

    }
}
