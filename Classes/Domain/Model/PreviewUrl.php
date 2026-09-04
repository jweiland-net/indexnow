<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\IndexNow\Domain\Model;

/**
 * A single frontend preview URL contributed via ProvidePreviewUrlEvent.
 *
 * $pageUid identifies the page (and therefore the site) this URL
 * actually renders on. Left null, it defaults to the page that
 * triggered the DataHandler change, which is correct for the
 * common case of a record rendered on that very page.
 *
 * Set it explicitly whenever the URL renders elsewhere, for
 * example a plugin detail view on a different page tree/site.
 * It is used to look up the correct site and its
 * "indexnow.apiKey" when notifying the search engine, so an
 * incorrect or missing value here resolves the wrong site's
 * API key.
 */
final readonly class PreviewUrl
{
    public function __construct(
        private string $url,
        private ?int $pageUid = null,
    ) {}

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getPageUid(): ?int
    {
        return $this->pageUid;
    }
}
