<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Index now',
    'description' => 'TYPO3 extension to send a request to indexnow.org for re-indexing after modifying content',
    'category' => 'service',
    'author' => 'Stefan Froemken',
    'author_email' => 'sfroemken@jweiland.net',
    'state' => 'experimental',
    'version' => '0.0.4',
    'constraints' => [
        'depends' => [
            'typo3' => '10.4.19-11.5.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
