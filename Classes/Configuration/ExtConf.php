<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\IndexNow\Configuration;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

/**
 * This class streamlines all settings from the extension manager
 */
#[Autoconfigure(constructor: 'create')]
class ExtConf
{
    private const EXT_KEY = 'indexnow';

    private const DEFAULT_SETTINGS = [
        'searchEngineEndpoint' => '',
        'notifyBatchMode' => false,
    ];

    public function __construct(
        private readonly string $searchEngineEndpoint = self::DEFAULT_SETTINGS['searchEngineEndpoint'],
        private readonly bool $notifyBatchMode = self::DEFAULT_SETTINGS['notifyBatchMode'],
    ) {}

    public static function create(ExtensionConfiguration $extensionConfiguration): self
    {
        $extensionSettings = self::DEFAULT_SETTINGS;

        // Overwrite default extension settings with values from EXT_CONF
        try {
            $extensionSettings = array_merge(
                $extensionSettings,
                $extensionConfiguration->get(self::EXT_KEY),
            );
        } catch (ExtensionConfigurationExtensionNotConfiguredException|ExtensionConfigurationPathDoesNotExistException) {
        }

        return new self(
            searchEngineEndpoint: (string)$extensionSettings['searchEngineEndpoint'],
            notifyBatchMode: (bool)$extensionSettings['notifyBatchMode'],
        );
    }

    public function getSearchEngineEndpoint(): string
    {
        if ($this->searchEngineEndpoint === '') {
            return 'https://www.bing.com/indexnow';
        }

        return $this->searchEngineEndpoint;
    }

    public function isNotifyBatchMode(): bool
    {
        return (bool)$this->notifyBatchMode;
    }
}
