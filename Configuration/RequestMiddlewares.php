<?php

use JWeiland\IndexNow\Middleware\ApiKeyVerificationMiddleware;

return [
    'frontend' => [
        'jweiland/indexnow/api-key-verification' => [
            'target' => ApiKeyVerificationMiddleware::class,
            'after' => [
                'typo3/cms-core/normalized-params-attribute',
            ],
            'before' => [
                'typo3/cms-frontend/site',
            ],
        ],
    ],
];
