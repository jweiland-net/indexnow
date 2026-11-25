<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\IndexNow\Configuration\Exception;

use TYPO3\CMS\Core\Exception;

/**
 * Thrown if no IndexNow API key is configured in the site settings of a page
 */
final class ApiKeyNotAvailableException extends Exception {}
