<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\IndexNow\Hook;

use JWeiland\IndexNow\Configuration\Exception\ApiKeyNotAvailableException;
use JWeiland\IndexNow\Configuration\ExtConf;
use JWeiland\IndexNow\Domain\Repository\StackRepository;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Routing\UnableToLinkToPageException;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Hook into DataHandler to submit a re-index request to indexnow.org
 */
class DataHandlerHook
{
    /**
     * @var ExtConf
     */
    protected $extConf;

    /**
     * @var RequestFactory
     */
    protected $requestFactory;

    /**
     * @var StackRepository
     */
    protected $stackRepository;

    /**
     * @var PageRenderer
     */
    protected $pageRenderer;

    public function __construct(
        ExtConf $extConf,
        RequestFactory $requestFactory,
        StackRepository $stackRepository,
        PageRenderer $pageRenderer
    ) {
        $this->extConf = $extConf;
        $this->requestFactory = $requestFactory;
        $this->stackRepository = $stackRepository;
        $this->pageRenderer = $pageRenderer;
    }

    public function processDatamap_beforeStart(DataHandler $dataHandler): void
    {
        foreach ($dataHandler->datamap as $table => $records) {
            foreach ($records as $uid => $record) {
                $mergedRecord = $this->getMergedRecord($uid, $table, $record);
                if ($mergedRecord === []) {
                    continue;
                }

                $pageUid = $this->getPageUid($mergedRecord, $table);
                if ($pageUid <= 0) {
                    continue;
                }

                try {
                    $url = $this->getPreviewUrl($pageUid);
                    if ($url === null) {
                        continue;
                    }

                    $this->stackRepository->insert(
                        $this->getUrlForSearchEngineEndpoint($url)
                    );
                } catch (ApiKeyNotAvailableException $apiKeyNotAvailableException) {
                    $this->sendBackendNotification(
                        'error',
                        'Missing API key',
                        'Please set an API key for EXT:indexnow in extension settings'
                    );

                    break 2;
                }
            }
        }
    }

    protected function getPageUid(array $record, string $table): int
    {
        $pid = 0;
        if (isset($record['uid'], $record['pid'])) {
            $pid = $table === 'pages' ? $record['uid'] : $record['pid'];
        }

        return (int)$pid;
    }

    protected function sendBackendNotification(string $status, string $title, string $message): void
    {
        $this->pageRenderer->loadRequireJsModule(
            'TYPO3/CMS/Backend/Notification',
            sprintf(
                'function(Notification) { Notification.%s("%s", "%s") }',
                $status,
                $title,
                $message
            )
        );
    }

    protected function getPreviewUrl(int $pageUid): ?string
    {
        $anchorSection = '';
        $additionalParams = '';

        try {
            return htmlspecialchars(
                BackendUtility::getPreviewUrl(
                    $pageUid,
                    '',
                    null,
                    $anchorSection,
                    '',
                    $additionalParams
                )
            );
        } catch (UnableToLinkToPageException $e) {
            return null;
        }
    }

    protected function getUrlForSearchEngineEndpoint(string $url): string
    {
        $urlForSearchEngine = str_replace(
            [
                '###URL###',
                '###APIKEY###'
            ],
            [
                $url,
                $this->extConf->getApiKey()
            ],
            $this->extConf->getSearchEngineEndpoint()
        );

        if ($this->extConf->isEnableDebug()) {
            $this->sendBackendNotification(
                'info',
                'Debug URL to searchengine',
                'URL: ' . $urlForSearchEngine
            );
        }

        return $urlForSearchEngine;
    }

    /**
     * If NEW, $recordFromRequest will contain nearly all fields.
     * If updated, $recordFromRequest will only contain modified fields. PID f.e. is missing
     * Use this method to get a merged record (DB and Request).
     *
     * @param string|int $uid
     */
    protected function getMergedRecord($uid, string $table, array $recordFromRequest): array
    {
        if (!MathUtility::canBeInterpretedAsInteger($uid)) {
            return $recordFromRequest;
        }

        $record = BackendUtility::getRecord($table, (int)$uid, 'uid,pid');
        ArrayUtility::mergeRecursiveWithOverrule(
            $record,
            $recordFromRequest
        );

        return $record;
    }
}
