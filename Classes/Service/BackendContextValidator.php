<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\IndexNow\Service;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

/**
 * PreviewUriBuilder::buildUri() requires a real, persisted backend user session (FormProtection).
 * DataHandler can also run without one, for example via CLI commands or MCP servers that construct
 * a stub BackendUserAuthentication. IndexNow is a passive side effect of an editor's change and must
 * never abort it, so processing is restricted to authenticated editors working in the live workspace.
 */
readonly class BackendContextValidator
{
    public function isLiveEditorContext(): bool
    {
        $backendUser = $GLOBALS['BE_USER'] ?? null;
        if (!$backendUser instanceof BackendUserAuthentication) {
            return false;
        }

        if ($backendUser->workspace !== 0) {
            return false;
        }

        $userUid = $backendUser->user['uid'] ?? 0;

        return is_numeric($userUid) && (int)$userUid > 0;
    }
}
