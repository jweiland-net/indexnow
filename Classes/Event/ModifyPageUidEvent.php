<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\IndexNow\Event;

/**
 * Event to modify the page UID. Useful to prevent re-indexing the page.
 * Set the page UID to 0 to disable the IndexNow request.
 */
final class ModifyPageUidEvent
{
    /**
     * @param array<string, mixed> $record The record, which is requested to be stored. Coming from DataHandler.
     * @param string $table The table name where the record will be stored
     * @param int $pageUid The page UID used to create a preview URL. Set it to 0 to prevent informing IndexNow.
     * @param array<string, mixed>|null $pageRecord The full page record. Be careful, in rare cases it can be NULL!
     */
    public function __construct(
        private readonly array $record,
        private readonly string $table,
        private int $pageUid,
        private readonly ?array $pageRecord,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getRecord(): array
    {
        return $this->record;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getPageUid(): int
    {
        return $this->pageUid;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getPageRecord(): ?array
    {
        return $this->pageRecord;
    }

    /**
     * @param int $pageUid Set to 0 to NOT inform IndexNow about changed content
     */
    public function setPageUid(int $pageUid): void
    {
        $this->pageUid = $pageUid;
    }
}
