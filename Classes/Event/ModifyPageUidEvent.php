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
 * Return 0 to disable IndexNow request.
 */
class ModifyPageUidEvent
{
    /**
     * @param array $record This is the record, which is requested to be stored. Coming from DataHandler.
     * @param string $table This is the table name where the record will be stored
     * @param int $pageUid This is the page UID. We will use it to create a preview URL for IndexNow request. Set it to 0 to prevent informing IndexNow.
     * @param array|null $pageRecord To keep your life easy we provide you the full page record. Be careful, in rare cases it can be NULL!
     */
    public function __construct(
        private readonly array $record,
        private readonly string $table,
        private int $pageUid,
        private readonly ?array $pageRecord,
    ) {}

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
