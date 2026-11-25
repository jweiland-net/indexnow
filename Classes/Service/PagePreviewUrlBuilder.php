<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\IndexNow\Service;

use TYPO3\CMS\Backend\Routing\PreviewUriBuilder;
use TYPO3\CMS\Core\Routing\UnableToLinkToPageException;

/**
 * Builds the frontend URL of a page with TYPO3's PreviewUriBuilder
 */
readonly class PagePreviewUrlBuilder
{
    public function build(int $pageUid, int $languageUid = 0): ?string
    {
        try {
            $previewUriBuilder = PreviewUriBuilder::create($pageUid);
            if ($languageUid > 0) {
                $previewUriBuilder = $previewUriBuilder->withLanguage($languageUid);
            }

            $previewUri = $previewUriBuilder->buildUri();
        } catch (UnableToLinkToPageException) {
            return null;
        }

        $previewUrl = (string)$previewUri;

        return $previewUrl === '' ? null : $previewUrl;
    }
}
