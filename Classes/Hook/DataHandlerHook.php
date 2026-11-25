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
use JWeiland\IndexNow\Service\LanguageResolver;
use JWeiland\IndexNow\Service\PageUidResolver;
use JWeiland\IndexNow\Service\PreviewUrlCollector;
use JWeiland\IndexNow\Service\RecordResolver;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\DataHandling\DataHandler;

/**
 * Hook into DataHandler to queue the preview URLs of modified records for IndexNow
 */
#[Autoconfigure(public: true)]
readonly class DataHandlerHook
{
    public function __construct(
        private StackRepository $stackRepository,
        private BackendContextValidator $backendContextValidator,
        private RecordResolver $recordResolver,
        private PageUidResolver $pageUidResolver,
        private LanguageResolver $languageResolver,
        private PreviewUrlCollector $previewUrlCollector,
    ) {}

    public function processDatamap_beforeStart(DataHandler $dataHandler): void
    {
        if (!$this->backendContextValidator->isLiveEditorContext()) {
            return;
        }

        foreach ($dataHandler->datamap as $table => $records) {
            foreach ($records as $recordUid => $recordFromRequest) {
                $this->processRecord((string)$table, $recordUid, $recordFromRequest);
            }
        }
    }

    /**
     * @param array<string, mixed> $recordFromRequest
     */
    private function processRecord(string $table, int|string $recordUid, array $recordFromRequest): void
    {
        $record = $this->recordResolver->resolve($table, $recordUid, $recordFromRequest);
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
}
