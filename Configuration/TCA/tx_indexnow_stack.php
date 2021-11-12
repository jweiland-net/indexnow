<?php
return [
    'ctrl' => [
        'title' => 'IndexNow URL Stack',
        'label' => 'url',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'hideTable' => true,
    ],
    'types' => [
        '1' => ['showitem' => 'url'],
    ],
    'palettes' => [],
    'columns' => [
        'cruser_id' => [
            'label' => 'cruser_id',
            'config' => [
                'type' => 'passthrough'
            ]
        ],
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
