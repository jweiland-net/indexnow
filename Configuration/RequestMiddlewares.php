<?php

return [
    'frontend' => [
        'jweiland/indexnow/api-key-verification' => [
            'target' => \JWeiland\IndexNow\Middleware\ApiKeyVerificationMiddleware::class,
            'after' => [
                'typo3/cms-core/normalized-params-attribute',
            ],
            'before' => [
                'typo3/cms-frontend/site',
            ],
        ],
    ],
];
