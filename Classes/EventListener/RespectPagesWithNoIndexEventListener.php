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

/**
 * Prevent informing IndexNow about pages with the activated no_index property.
 */
class RespectPagesWithNoIndexEventListener
{
    public function __invoke(ModifyPageUidEvent $modifyPageUidEvent): void
    {
        $pageRecord = $modifyPageUidEvent->getPageRecord();
        if (!is_array($pageRecord)) {
            return;
        }

        if (!array_key_exists('no_index', $pageRecord)) {
            return;
        }

        if ((int)$pageRecord['no_index'] === 1) {
            $modifyPageUidEvent->setPageUid(0);
        }
    }
}
