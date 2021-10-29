<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Index now',
    'description' => 'TYPO3 extension to send a request to indexnow.org for re-indexing after modifying content',
    'category' => 'service',
    'author' => 'Stefan Froemken',
    'author_email' => 'sfroemken@jweiland.net',
    'clearCacheOnLoad' => 1,
    'version' => '0.0.2',
    'constraints' => [
        'depends' => [
            'typo3' => '10.4.19-10.5.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
