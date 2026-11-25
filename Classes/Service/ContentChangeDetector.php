<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\IndexNow\Service;

use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\Field\RelationalFieldTypeInterface;
use TYPO3\CMS\Core\Schema\RelationshipType;
use TYPO3\CMS\Core\Schema\TcaSchema;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;

/**
 * Decides, if the fields DataHandler has written to a record are a change of content worth informing IndexNow.
 *
 * DataHandler only passes fields whose values differ from the persisted record. Some of them are pure
 * bookkeeping, which DataHandler refreshes on every save, even if no content has changed:
 *  - the "updated at" field (f.e. "tstamp")
 *  - the translation diff source (f.e. "l10n_diffsource"), a snapshot of the default language values
 *  - "l10n_state", which TYPO3's DataMapProcessor writes into every dependent translation
 *
 * Fields with MM relations (f.e. "categories") are always passed by DataHandler, as it does not compare
 * them with the persisted value (see DataHandler::compareFieldArrayWithCurrentAndUnset()). As the old
 * relations are not available anymore, they can not be used to detect a change. Changing only the MM
 * relations of a record does therefore not inform IndexNow.
 */
final readonly class ContentChangeDetector
{
    private const BOOKKEEPING_FIELDS = ['l10n_state'];

    public function __construct(
        private TcaSchemaFactory $tcaSchemaFactory,
    ) {}

    /**
     * @param array<string, mixed> $changedFields
     */
    public function hasContentChanges(string $table, array $changedFields): bool
    {
        $contentFields = array_diff_key($changedFields, array_flip($this->getBookkeepingFields($table)));
        if ($contentFields === [] || !$this->tcaSchemaFactory->has($table)) {
            return $contentFields !== [];
        }

        $schema = $this->tcaSchemaFactory->get($table);
        foreach (array_keys($contentFields) as $fieldName) {
            if (!$this->isManyToManyField($schema, (string)$fieldName)) {
                return true;
            }
        }

        return false;
    }

    private function isManyToManyField(TcaSchema $schema, string $fieldName): bool
    {
        if (!$schema->hasField($fieldName)) {
            return false;
        }

        $field = $schema->getField($fieldName);

        return $field instanceof RelationalFieldTypeInterface
            && $field->getRelationshipType() === RelationshipType::ManyToMany;
    }

    /**
     * @return list<string>
     */
    private function getBookkeepingFields(string $table): array
    {
        $bookkeepingFields = self::BOOKKEEPING_FIELDS;
        if (!$this->tcaSchemaFactory->has($table)) {
            return $bookkeepingFields;
        }

        $schema = $this->tcaSchemaFactory->get($table);
        if ($schema->hasCapability(TcaSchemaCapability::UpdatedAt)) {
            $bookkeepingFields[] = $schema->getCapability(TcaSchemaCapability::UpdatedAt)->getFieldName();
        }

        if ($schema->isLanguageAware()) {
            $diffSourceField = $schema->getCapability(TcaSchemaCapability::Language)->getDiffSourceField();
            if ($diffSourceField !== null) {
                $bookkeepingFields[] = $diffSourceField->getName();
            }
        }

        return $bookkeepingFields;
    }
}
