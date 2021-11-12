<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\IndexNow\Service;

use GuzzleHttp\Exception\ClientException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/*
 * Service to communicate with the search engine
 */
class SearchEngineService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var RequestFactory
     */
    protected $requestFactory;

    public function __construct(RequestFactory $requestFactory)
    {
        $this->requestFactory = $requestFactory;
    }

    public function notifySearchEngine(string $url): bool
    {
        $isValidRequest = false;
        if (GeneralUtility::isValidUrl($url)) {
            try {
                $response = $this->requestFactory->request($url);
                $statusCode = $response->getStatusCode();

                if ($statusCode !== 200) {
                    $this->logger->log(
                        LogLevel::WARNING,
                        sprintf(
                            'Request to indexnow.org results in StatusCode %d with message: %s',
                            $statusCode,
                            (string)$response->getBody()
                        )
                    );
                } else {
                    $isValidRequest = true;
                }
            } catch (ClientException $e) {
                $this->logger->log(
                    LogLevel::ERROR,
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
