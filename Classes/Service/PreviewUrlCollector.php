<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\IndexNow\Service;

use JWeiland\IndexNow\Domain\Model\PreviewUrl;
use JWeiland\IndexNow\Event\ProvidePreviewUrlEvent;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Collects the preview URLs of a record. Listeners of ProvidePreviewUrlEvent win; for example,
 * because the record is rendered on several pages or sites. Otherwise, the page preview URL is used.
 */
readonly class PreviewUrlCollector
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private PagePreviewUrlBuilder $pagePreviewUrlBuilder,
    ) {}

    /**
     * @param array<string, mixed> $record
     * @return list<PreviewUrl>
     */
    public function collect(string $table, int|string $recordUid, array $record, int $pageUid, int $languageUid): array
    {
        /** @var ProvidePreviewUrlEvent $providePreviewUrlEvent */
        $providePreviewUrlEvent = $this->eventDispatcher->dispatch(
            new ProvidePreviewUrlEvent($table, $recordUid, $record),
        );

        $previewUrls = $providePreviewUrlEvent->getPreviewUrls();
        if ($previewUrls !== []) {
            return $previewUrls;
        }

        $previewUrl = $this->pagePreviewUrlBuilder->build($pageUid, $languageUid);

        return $previewUrl === null ? [] : [new PreviewUrl($previewUrl, $pageUid)];
    }
}
