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

/**
 * Prevent informing IndexNow about pages with the activated no_index property.
 */
#[AsEventListener(identifier: 'respectPagesWithNoIndexEventListener')]
final readonly class RespectPagesWithNoIndexEventListener
{
    public function __invoke(ModifyPageUidEvent $modifyPageUidEvent): void
    {
        $noIndex = $modifyPageUidEvent->getPageRecord()['no_index'] ?? 0;

        if (is_numeric($noIndex) && (int)$noIndex === 1) {
            $modifyPageUidEvent->setPageUid(0);
        }
    }
}
