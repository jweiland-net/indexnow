<?php

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

return [
    'ctrl' => [
        'title' => 'IndexNow URL Stack',
        'label' => 'url',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'hideTable' => true,
    ],
    'types' => [
        '1' => ['showitem' => 'url'],
    ],
    'palettes' => [],
    'columns' => [
        'crdate' => [
            'label' => 'crdate',
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'tstamp' => [
            'label' => 'tstamp',
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'url' => [
            'label' => 'URL',
            'config' => [
                'type' => 'passthrough',
            ],
        ],
    ],
];
