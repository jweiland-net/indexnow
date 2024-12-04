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
use JWeiland\IndexNow\Event\ModifyPageUidEvent;
use TYPO3\CMS\Backend\Routing\PreviewUriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Routing\UnableToLinkToPageException;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Hook into DataHandler to submit a re-index request to indexnow.org
 */
readonly class DataHandlerHook
{
    public function __construct(
        protected ExtConf $extConf,
        protected RequestFactory $requestFactory,
        protected StackRepository $stackRepository,
        protected PageRenderer $pageRenderer,
        protected FlashMessageService $flashMessageService,
        protected EventDispatcher $eventDispatcher
    ) {}

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
                        'Missing API key',
                        'Please set an API key for EXT:indexnow in extension settings',
                        ContextualFeedbackSeverity::ERROR
                    );

                    break 2;
                }
            }
        }
    }

    protected function getPageUid(array $recordToBeStored, string $table): int
    {
        $pageUid = 0;
        if (isset($recordToBeStored['uid'], $recordToBeStored['pid'])) {
            $pageUid = (int)($table === 'pages' ? $recordToBeStored['uid'] : $recordToBeStored['pid']);
        }

        if ($table === 'pages') {
            $pageRecord = $recordToBeStored;
        } else {
            $pageRecord = BackendUtility::getRecord('pages', $pageUid);
        }

        /** @var ModifyPageUidEvent $modifyPageUidEvent */
        $modifyPageUidEvent = $this->eventDispatcher->dispatch(
            new ModifyPageUidEvent($recordToBeStored, $table, $pageUid, $pageRecord)
        );

        return $modifyPageUidEvent->getPageUid();
    }

    protected function sendBackendNotification(
        string $title,
        string $message,
        ContextualFeedbackSeverity $severity = ContextualFeedbackSeverity::OK
    ): void {
        $flashMessage = GeneralUtility::makeInstance(
            FlashMessage::class,
            $message,
            $title,
            $severity,
            true
        );

        $messageQueue = $this->flashMessageService->getMessageQueueByIdentifier();
        $messageQueue->addMessage($flashMessage);
    }

    protected function getPreviewUrl(int $pageUid): ?string
    {
        $anchorSection = '';
        $additionalParams = '';

        try {
            return htmlspecialchars(
                (string)PreviewUriBuilder::create($pageUid)->withRootLine(null)->withSection($anchorSection)->withAdditionalQueryParameters($additionalParams)->buildUri()
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
                '###APIKEY###',
            ],
            [
                $url,
                $this->extConf->getApiKey(),
            ],
            $this->extConf->getSearchEngineEndpoint()
        );

        if ($this->extConf->isEnableDebug()) {
            $this->sendBackendNotification(
                'Debug URL to searchengine',
                'URL: ' . $urlForSearchEngine,
                ContextualFeedbackSeverity::INFO
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
