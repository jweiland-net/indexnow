<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\IndexNow\Hook;

use JWeiland\IndexNow\Domain\Repository\StackRepository;
use JWeiland\IndexNow\Event\ModifyPageUidEvent;
use JWeiland\IndexNow\Notifier\SingleNotificationTrait;
use TYPO3\CMS\Backend\Routing\PreviewUriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
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
class DataHandlerHook
{
    use SingleNotificationTrait;

    public function __construct(
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

                // get language from mergedRecord -> sys_language_uid only works if table != pages
                $sysLanguageUid = 0;
                if (isset($mergedRecord['sys_language_uid']) && $mergedRecord['sys_language_uid'] > 0) {
                    $sysLanguageUid = (int)$mergedRecord['sys_language_uid'];
                }

                // if the table is pages, we need to get sys_language_uid form $request object
                if ($table === 'pages') {
                    if (isset($GLOBALS['TYPO3_REQUEST']) && $GLOBALS['TYPO3_REQUEST'] instanceof \Psr\Http\Message\ServerRequestInterface) {
                        $queryParams = $GLOBALS['TYPO3_REQUEST']->getQueryParams();
                        if (isset($queryParams['overrideVals']['pages']['sys_language_uid'])) {
                            $sysLanguageUid = (int)$queryParams['overrideVals']['pages']['sys_language_uid'];
                        }
                    }
                }

                if ($mergedRecord === []) {
                    continue;
                }

                $pageUid = $this->getPageUid($mergedRecord, $table);
                if ($pageUid <= 0) {
                    continue;
                }

                $url = $this->getPreviewUrl($pageUid, $sysLanguageUid);
                if ($url === null) {
                    continue;
                }

                $this->stackRepository->insert($url);
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

    protected function getPreviewUrl(int $pageUid, int $sysLanguageUid = 0): ?string
    {
        $anchorSection = '';
        $additionalParams = '';

        try {
            $previewUriBuilder = PreviewUriBuilder::create($pageUid)
                ->withSection($anchorSection)
                ->withAdditionalQueryParameters($additionalParams);

            // Only set language if it's different from default
            if ($sysLanguageUid > 0) {
                $previewUriBuilder = $previewUriBuilder->withLanguage($sysLanguageUid);
            }
            return htmlspecialchars((string)$previewUriBuilder->buildUri());
        } catch (UnableToLinkToPageException) {
            return null;
        }
    }

    /**
     * If NEW, $recordFromRequest will contain nearly all fields.
     * If updated, $recordFromRequest will only contain modified fields. PID f.e. is missing.
     * Use this method to get a merged record (DB and Request).
     */
    protected function getMergedRecord(int|string $uid, string $table, array $recordFromRequest): array
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
