<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\IndexNow\Service;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Resolves the language UID of a record which is processed by DataHandler
 */
readonly class LanguageResolver
{
    /**
     * @param array<string, mixed> $record
     */
    public function resolve(string $table, array $record): int
    {
        $languageUid = $this->toPositiveInt($record['sys_language_uid'] ?? 0);

        // When creating a page translation, the language is only available in the request
        if ($table === 'pages') {
            return $this->getPageLanguageFromRequest() ?? $languageUid;
        }

        return $languageUid;
    }

    private function getPageLanguageFromRequest(): ?int
    {
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
        if (!$request instanceof ServerRequestInterface) {
            return null;
        }

        $overrideValues = $request->getQueryParams()['overrideVals'] ?? null;
        if (!is_array($overrideValues) || !is_array($overrideValues['pages'] ?? null)) {
            return null;
        }

        if (!isset($overrideValues['pages']['sys_language_uid'])) {
            return null;
        }

        return $this->toPositiveInt($overrideValues['pages']['sys_language_uid']);
    }

    private function toPositiveInt(mixed $value): int
    {
        return is_numeric($value) ? max(0, (int)$value) : 0;
    }
}
