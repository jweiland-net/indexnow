<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\IndexNow\Event;

use JWeiland\IndexNow\Domain\Model\PreviewUrl;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Event to allow third-party extensions to provide frontend preview URLs for a record.
 *
 * The "indexnow" extension can generate preview URLs for core records like "pages" and "tt_content"
 * via TYPO3 API. For custom tables, the extension usually cannot know where (and on which site/root
 * page) the corresponding detail view is rendered, nor which plugin/action and site configuration is
 * required to build a valid URL. A record can also be rendered on more than one page, for example on
 * multiple page trees/sites, or via plugins like FAQ or review listings placed on several pages.
 *
 * By listening to this event, extension authors can contribute their own preview URL generation for
 * their records and add the resulting URLs via {@see addPreviewUrl()}, once per rendering page.
 */
class ProvidePreviewUrlEvent
{
    /**
     * @var PreviewUrl[]
     */
    private array $previewUrls = [];

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

    /**
     * @param int|null $pageUid The page this URL actually renders on, if it differs from the
     *                          page that triggered the DataHandler change (e.g. another site).
     */
    public function addPreviewUrl(string $previewUrl, ?int $pageUid = null): void
    {
        if (!GeneralUtility::isValidUrl($previewUrl)) {
            return;
        }

        $this->previewUrls[] = new PreviewUrl($previewUrl, $pageUid);
    }

    /**
     * @return PreviewUrl[]
     */
    public function getPreviewUrls(): array
    {
        return $this->previewUrls;
    }

    /**
     * @deprecated since indexnow 0.0.11, will be removed in indexnow 1.0.0
     */
    public function setPreviewUrl(string $previewUrl): void
    {
        trigger_error(
            'ProvidePreviewUrlEvent::setPreviewUrl() is deprecated and will be removed in indexnow 1.0.0. '
            . 'Use addPreviewUrl() instead, it supports more than one URL per record.',
            E_USER_DEPRECATED,
        );

        $this->addPreviewUrl($previewUrl);
    }

    /**
     * @deprecated since indexnow 0.0.11, will be removed in indexnow 1.0.0
     */
    public function getPreviewUrl(): string
    {
        trigger_error(
            'ProvidePreviewUrlEvent::getPreviewUrl() is deprecated and will be removed in indexnow 1.0.0. '
            . 'Use getPreviewUrls() instead, it returns more than one URL per record.',
            E_USER_DEPRECATED,
        );

        return ($this->previewUrls[0] ?? null)?->getUrl() ?? '';
    }
}
