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
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Service to communicate with the search engine
 */
readonly class SearchEngineNotifier
{
    public function __construct(
        protected RequestFactory $requestFactory,
        protected LoggerInterface $logger
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
}
