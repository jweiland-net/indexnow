<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\IndexNow\EventListener;

use JWeiland\IndexNow\Event\ModifyPageUidEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;

/**
 * Prevent informing IndexNow about hidden (disabled) pages, as their URLs are not reachable in frontend.
 *
 * The page record is read after DataHandler has written it: hiding a page skips it, making it visible
 * again is a change of the "disabled" field and queues its URL.
 */
#[AsEventListener(identifier: 'respectHiddenPagesEventListener')]
final readonly class RespectHiddenPagesEventListener
{
    public function __construct(
        private TcaSchemaFactory $tcaSchemaFactory,
    ) {}

    public function __invoke(ModifyPageUidEvent $modifyPageUidEvent): void
    {
        $disabledFieldName = $this->getDisabledFieldName();
        if ($disabledFieldName === null) {
            return;
        }

        $isDisabled = $modifyPageUidEvent->getPageRecord()[$disabledFieldName] ?? 0;

        if (is_numeric($isDisabled) && (int)$isDisabled === 1) {
            $modifyPageUidEvent->setPageUid(0);
        }
    }

    private function getDisabledFieldName(): ?string
    {
        if (!$this->tcaSchemaFactory->has('pages')) {
            return null;
        }

        $schema = $this->tcaSchemaFactory->get('pages');
        if (!$schema->hasCapability(TcaSchemaCapability::RestrictionDisabledField)) {
            return null;
        }

        return $schema->getCapability(TcaSchemaCapability::RestrictionDisabledField)->getFieldName();
    }
}
