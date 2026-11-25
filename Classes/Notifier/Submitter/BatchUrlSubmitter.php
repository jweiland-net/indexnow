<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\IndexNow\Notifier\Submitter;

use JWeiland\IndexNow\Configuration\ApiKeyResolver;
use JWeiland\IndexNow\Configuration\Exception\ApiKeyNotAvailableException;
use JWeiland\IndexNow\Configuration\ExtConf;
use JWeiland\IndexNow\Domain\Model\Stack;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Http\RequestFactory;

/**
 * Submits all URLs of one host with a single JSON POST request (chunked by the IndexNow limit).
 * Used if batch mode is enabled and a host has at least two URLs.
 */
readonly class BatchUrlSubmitter implements UrlSubmitterInterface
{
    /**
     * In batch mode, there is a limit of 10,000 URLs per POST request.
     */
    private const MAX_URLS_EACH_REQUEST = 10000;

    public function __construct(
        private RequestFactory $requestFactory,
        private ApiKeyResolver $apiKeyResolver,
        private ExtConf $extConf,
        private LoggerInterface $logger,
    ) {}

    public function supports(int $numberOfStacks): bool
    {
        return $this->extConf->isNotifyBatchMode() && $numberOfStacks >= 2;
    }

    public function submit(string $host, array $stacks): bool
    {
        try {
            $apiKey = $this->apiKeyResolver->getApiKeyForPage($stacks[0]->getTargetPid());
        } catch (ApiKeyNotAvailableException $apiKeyNotAvailableException) {
            $this->logger->error($apiKeyNotAvailableException->getMessage());

            return false;
        }

        $isSuccessful = true;

        foreach (array_chunk($stacks, self::MAX_URLS_EACH_REQUEST) as $chunkedStacks) {
            $isSuccessful = $this->submitChunk($host, $apiKey, $chunkedStacks) && $isSuccessful;
        }

        return $isSuccessful;
    }

    /**
     * @param list<Stack> $stacks
     */
    private function submitChunk(string $host, string $apiKey, array $stacks): bool
    {
        $postData = [
            'host' => $host,
            'key' => $apiKey,
            'keyLocation' => 'https://' . $host . '/' . $apiKey . '.txt',
            'urlList' => array_map(static fn(Stack $stack): string => $stack->getUrl(), $stacks),
        ];

        try {
            $response = $this->requestFactory->request(
                $this->extConf->getSearchEngineEndpoint(),
                'POST',
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'body' => json_encode($postData, JSON_THROW_ON_ERROR),
                ],
            );
        } catch (\Throwable $throwable) {
            $this->logger->error(
                'Batch IndexNow error for domain {host}: {message}',
                ['host' => $host, 'message' => $throwable->getMessage()],
            );

            return false;
        }

        $statusCode = $response->getStatusCode();
        if (in_array($statusCode, [200, 202], true)) {
            $this->logger->info(
                'Batch IndexNow successful for domain {host}: {count} URLs sent.',
                ['host' => $host, 'count' => count($stacks)],
            );

            return true;
        }

        $this->logger->warning(
            'Batch IndexNow failed for domain {host} with status {statusCode} and message: {message}',
            ['host' => $host, 'statusCode' => $statusCode, 'message' => (string)$response->getBody()],
        );

        return false;
    }
}
