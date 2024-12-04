<?php

if (!defined('TYPO3')) {
    die('Access denied.');
}

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['indexnow']
    = \JWeiland\IndexNow\Hook\DataHandlerHook::class;

$GLOBALS['TYPO3_CONF_VARS']['LOG']['JWeiland']['IndexNow']['writerConfiguration'] = [
    \Psr\Log\LogLevel::WARNING => [
        \TYPO3\CMS\Core\Log\Writer\FileWriter::class => [
            'logFileInfix' => 'indexnow',
        ],
    ],
];
