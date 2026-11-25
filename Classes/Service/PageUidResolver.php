<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\IndexNow\Service;

use JWeiland\IndexNow\Event\ModifyPageUidEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Resolves the page UID a record is rendered on. Listeners of ModifyPageUidEvent
 * can change the page UID or set it to 0 to prevent informing IndexNow.
 */
readonly class PageUidResolver
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    /**
     * @param array<string, mixed> $record
     */
    public function resolve(string $table, array $record): int
    {
        $pageUid = $this->getPageUidFromRecord($table, $record);

        // DataHandler has already written the record, so the database contains the full and current page record.
        // The given record may only contain the changed fields.
        $pageRecord = BackendUtility::getRecord('pages', $pageUid) ?? ($table === 'pages' ? $record : null);

        /** @var ModifyPageUidEvent $modifyPageUidEvent */
        $modifyPageUidEvent = $this->eventDispatcher->dispatch(
            new ModifyPageUidEvent($record, $table, $pageUid, $pageRecord),
        );

        return $modifyPageUidEvent->getPageUid();
    }

    /**
     * @param array<string, mixed> $record
     */
    private function getPageUidFromRecord(string $table, array $record): int
    {
        if (!isset($record['uid'], $record['pid'])) {
            return 0;
        }

        $pageUid = $table === 'pages' ? $record['uid'] : $record['pid'];

        return is_numeric($pageUid) ? (int)$pageUid : 0;
    }
}
