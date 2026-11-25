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
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * If NEW, the record from the data map contains nearly all fields.
 * If updated, the record from the data map only contains modified fields (f.e. "pid" is missing).
 * Translations added by TYPO3's DataMapProcessor (l10n_mode=exclude, allowLanguageSynchronization)
 * even only contain the synchronized fields.
 *
 * This class merges the persisted "uid", "pid" and language field with the record from the data map.
 */
final readonly class RecordResolver
{
    public function __construct(
        private TcaSchemaFactory $tcaSchemaFactory,
    ) {}

    /**
     * @param array<string, mixed> $recordFromDataMap
     * @return array<string, mixed>
     */
    public function resolve(string $table, int|string $uid, array $recordFromDataMap): array
    {
        if (!MathUtility::canBeInterpretedAsInteger($uid)) {
            return $recordFromDataMap;
        }

        $record = BackendUtility::getRecord($table, (int)$uid, implode(',', $this->getPersistedFieldNames($table))) ?? [];
        ArrayUtility::mergeRecursiveWithOverrule($record, $recordFromDataMap);

        return $record;
    }

    /**
     * @return list<string>
     */
    private function getPersistedFieldNames(string $table): array
    {
        $fieldNames = ['uid', 'pid'];
        if (!$this->tcaSchemaFactory->has($table)) {
            return $fieldNames;
        }

        $schema = $this->tcaSchemaFactory->get($table);
        if (!$schema->isLanguageAware()) {
            return $fieldNames;
        }

        $fieldNames[] = $schema->getCapability(TcaSchemaCapability::Language)->getLanguageField()->getName();

        return $fieldNames;
    }
}
