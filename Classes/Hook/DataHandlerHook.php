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
use JWeiland\IndexNow\Service\BackendContextValidator;
use JWeiland\IndexNow\Service\ContentChangeDetector;
use JWeiland\IndexNow\Service\LanguageResolver;
use JWeiland\IndexNow\Service\PageUidResolver;
use JWeiland\IndexNow\Service\PreviewUrlCollector;
use JWeiland\IndexNow\Service\RecordResolver;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Hook into DataHandler to queue the preview URLs of changed records for IndexNow.
 *
 * DataHandler calls this hook for every record of the data map after it has been written, including
 * the translations TYPO3 synchronized on its own (l10n_mode=exclude, allowLanguageSynchronization).
 * At this point $fieldArray only contains the fields whose values have actually changed, and the
 * database already contains the new values (f.e. a changed slug) to build the preview URLs from.
 * New records are processed with their final UID, which DataHandler maps from the temporary "NEW..." ID.
 */
#[Autoconfigure(public: true)]
readonly class DataHandlerHook
{
    public function __construct(
        private StackRepository $stackRepository,
        private BackendContextValidator $backendContextValidator,
        private ContentChangeDetector $contentChangeDetector,
        private RecordResolver $recordResolver,
        private PageUidResolver $pageUidResolver,
        private LanguageResolver $languageResolver,
        private PreviewUrlCollector $previewUrlCollector,
    ) {}

    /**
     * @param string $status "new" or "update"
     * @param int|string $id The record UID, or the temporary "NEW..." ID for new records
     * @param array<string, mixed> $fieldArray The fields which have been written to the database
     */
    public function processDatamap_afterDatabaseOperations(
        string $status,
        string $table,
        int|string $id,
        array $fieldArray,
        DataHandler $dataHandler,
    ): void {
        if (!$this->contentChangeDetector->hasContentChanges($table, $fieldArray)) {
            return;
        }

        if (!$this->backendContextValidator->isLiveEditorContext()) {
            return;
        }

        $recordUid = $this->getPersistedUid($id, $dataHandler);
        if ($recordUid === null) {
            return;
        }

        $record = $this->recordResolver->resolve($table, $recordUid, $fieldArray);
        if ($record === []) {
            return;
        }

        $pageUid = $this->pageUidResolver->resolve($table, $record);
        if ($pageUid <= 0) {
            return;
        }

        $previewUrls = $this->previewUrlCollector->collect(
            $table,
            $recordUid,
            $record,
            $pageUid,
            $this->languageResolver->resolve($table, $record),
        );

        foreach ($previewUrls as $previewUrl) {
            $this->stackRepository->insert($previewUrl->getUrl(), $previewUrl->getPageUid() ?? $pageUid);
        }
    }

    /**
     * Returns NULL, if a new record could not be inserted
     */
    private function getPersistedUid(int|string $id, DataHandler $dataHandler): ?int
    {
        $uid = MathUtility::canBeInterpretedAsInteger($id) ? $id : ($dataHandler->substNEWwithIDs[$id] ?? null);

        return MathUtility::canBeInterpretedAsInteger($uid) && (int)$uid > 0 ? (int)$uid : null;
    }
}
