<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\IndexNow\Notifier\Submitter;

use GuzzleHttp\Exception\ClientException;
use JWeiland\IndexNow\Configuration\ApiKeyResolver;
use JWeiland\IndexNow\Configuration\Exception\ApiKeyNotAvailableException;
use JWeiland\IndexNow\Configuration\ExtConf;
use JWeiland\IndexNow\Domain\Model\Stack;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Utility\HttpUtility;

/**
 * Submits each URL with its own GET request.
 * Used if batch mode is disabled or a host has only one URL.
 */
readonly class SingleUrlSubmitter implements UrlSubmitterInterface
{
    public function __construct(
        private RequestFactory $requestFactory,
        private ApiKeyResolver $apiKeyResolver,
        private ExtConf $extConf,
        private LoggerInterface $logger,
    ) {}

    public function supports(int $numberOfStacks): bool
    {
        return !$this->extConf->isNotifyBatchMode() || $numberOfStacks < 2;
    }

    /**
     * Do not catch ApiKeyNotAvailableException. It should break in scheduler/CLI
     * to visually inform the user about the missing API key.
     *
     * @throws ApiKeyNotAvailableException
     */
    public function submit(string $host, array $stacks): bool
    {
        $isSuccessful = true;

        foreach ($stacks as $stack) {
            $isSuccessful = $this->submitStack($stack) && $isSuccessful;
        }

        return $isSuccessful;
    }

    /**
     * @throws ApiKeyNotAvailableException
     */
    private function submitStack(Stack $stack): bool
    {
        if (!$stack->hasValidUrl()) {
            return false;
        }

        try {
            $response = $this->requestFactory->request($this->buildRequestUrl($stack));
        } catch (ClientException $clientException) {
            $this->logger->error(
                'Request to IndexNow results in error: {message}',
                ['message' => $clientException->getMessage()],
            );

            return false;
        }

        $statusCode = $response->getStatusCode();
        if ($statusCode === 200) {
            return true;
        }

        if ($statusCode === 202) {
            $this->logger->info('IndexNow received URL, but IndexNow key validation is still pending');

            return true;
        }

        $this->logger->warning(
            'Request to IndexNow results in status code {statusCode} with message: {message}',
            ['statusCode' => $statusCode, 'message' => (string)$response->getBody()],
        );

        return false;
    }

    /**
     * @throws ApiKeyNotAvailableException
     */
    private function buildRequestUrl(Stack $stack): string
    {
        $uri = (new Uri($this->extConf->getSearchEngineEndpoint()))->withQuery(
            HttpUtility::buildQueryString([
                'url' => $stack->getUrl(),
                'key' => $this->apiKeyResolver->getApiKeyForPage($stack->getTargetPid()),
            ]),
        );

        return (string)$uri;
    }
}
