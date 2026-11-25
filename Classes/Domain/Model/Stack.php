<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\IndexNow\Domain\Model;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A queued URL (record of table "tx_indexnow_stack") waiting to be sent to the IndexNow endpoint
 */
final readonly class Stack
{
    public function __construct(
        private int $uid,
        private string $url,
        private int $targetPid = 0,
    ) {}

    /**
     * Creates a stack from raw (f.e. database) values, which may contain surrounding whitespace
     */
    public static function fromRaw(int $uid, string $url, int $targetPid = 0): self
    {
        return new self($uid, trim($url), $targetPid);
    }

    public function getUid(): int
    {
        return $this->uid;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getTargetPid(): int
    {
        return $this->targetPid;
    }

    public function hasValidUrl(): bool
    {
        return $this->url !== '' && GeneralUtility::isValidUrl($this->url);
    }

    public function getHost(): string
    {
        $host = parse_url($this->url, PHP_URL_HOST);

        return is_string($host) ? $host : '';
    }
}
