<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\IndexNow\Event;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Event to allow third-party extensions to provide a frontend preview URL for a record.
 *
 * The "indexnow" extension can generate preview URLs for core records like "pages" and "tt_content"
 * via TYPO3 API. For custom tables, the extension usually cannot know where (and on which site/root
 * page) the corresponding detail view is rendered, nor which plugin/action and site configuration is
 * required to build a valid URL.
 *
 * By listening to this event, extension authors can contribute their own preview URL generation for
 * their records and set the resulting URL via {@see setPreviewUrl()}.
 */
class ProvidePreviewUrlEvent
{
    private string $previewUrl = '';

    /**
     * @param string $table This is the table name of the record to store
     * @param string|int $recordUid This is the record UID. Can be string if "NEW", else INT
     * @param array $record This is the record requested to be stored. Coming from DataHandler.
     */
    public function __construct(
        private readonly string $table,
        private readonly string|int $recordUid,
        private readonly array $record,
    ) {}

    public function getTable(): string
    {
        return $this->table;
    }

    public function getRecordUid(): int|string
    {
        return $this->recordUid;
    }

    public function getRecord(): array
    {
        return $this->record;
    }

    public function getPreviewUrl(): string
    {
        return $this->previewUrl;
    }

    public function setPreviewUrl(string $previewUrl): void
    {
        if (GeneralUtility::isValidUrl($previewUrl)) {
            $this->previewUrl = $previewUrl;
        }
    }
}
