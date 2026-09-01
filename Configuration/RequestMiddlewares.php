<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use JWeiland\IndexNow\Middleware\ApiKeyVerificationMiddleware;

return [
    'frontend' => [
        'jweiland/indexnow/api-key-verification' => [
            'target' => ApiKeyVerificationMiddleware::class,
            'after' => [
                'typo3/cms-frontend/site',
            ],
            'before' => [
                'typo3/cms-frontend/eid',
            ],
        ],
    ],
];
