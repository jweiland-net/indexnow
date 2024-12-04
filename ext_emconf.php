<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Index now',
    'description' => 'TYPO3 extension to inform various search engines over IndexNow endpoint about content updates',
    'category' => 'service',
    'author' => 'Stefan Froemken',
    'author_email' => 'sfroemken@jweiland.net',
    'state' => 'experimental',
    'version' => '0.0.4',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.15-12.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
