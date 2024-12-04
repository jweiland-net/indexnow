<?php
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
            ]
        ],
        'tstamp' => [
            'label' => 'tstamp',
            'config' => [
                'type' => 'passthrough',
            ]
        ],
        'url' => [
            'label' => 'URL',
            'config' => [
                'type' => 'passthrough',
            ],
        ],
    ]
];
