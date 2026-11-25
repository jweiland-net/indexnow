<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\IndexNow\Service;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * If NEW, the record from the request contains nearly all fields.
 * If updated, the record from the request only contains modified fields (f.e. "pid" is missing).
 * This class merges the persisted "uid" and "pid" with the record from the request.
 */
readonly class RecordResolver
{
    /**
     * @param array<string, mixed> $recordFromRequest
     * @return array<string, mixed>
     */
    public function resolve(string $table, int|string $uid, array $recordFromRequest): array
    {
        if (!MathUtility::canBeInterpretedAsInteger($uid)) {
            return $recordFromRequest;
        }

        $record = BackendUtility::getRecord($table, (int)$uid, 'uid,pid') ?? [];
        ArrayUtility::mergeRecursiveWithOverrule($record, $recordFromRequest);

        return $record;
    }
}
