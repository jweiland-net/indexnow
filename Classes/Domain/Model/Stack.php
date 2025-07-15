<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\IndexNow\Domain\Model;

class Stack
{
    public function __construct(
        private readonly int $uid,
        private readonly string $url,
    ) {}

    public function getUid(): int
    {
        return $this->uid;
    }

    public function getUrl(): string
    {
        return trim($this->url);
    }

    public function hasValidUrl(): bool
    {
        if ($this->getUrl() === '') {
            return false;
        }

        return filter_var($this->getUrl(), FILTER_VALIDATE_URL);
    }

    public function getHost(): string
    {
        $host = parse_url($this->getUrl(), PHP_URL_HOST);

        return is_string($host) ? $host : '';
    }
}
