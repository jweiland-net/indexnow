<?php

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

$EM_CONF[$_EXTKEY] = [
    'title' => 'Index now',
    'description' => 'TYPO3 extension to inform various search engines over IndexNow endpoint about content updates',
    'category' => 'service',
    'author' => 'Stefan Froemken',
    'author_email' => 'sfroemken@jweiland.net',
    'state' => 'experimental',
    'version' => '0.0.7',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.15-13.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
