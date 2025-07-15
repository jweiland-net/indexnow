<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\IndexNow\Configuration;

use JWeiland\IndexNow\Configuration\Exception\ApiKeyNotAvailableException;
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
        'apiKey' => '',
        'searchEngineEndpoint' => '',
        'enableDebug' => false,
        'notifyBatchMode' => false,
    ];

    public function __construct(
        private readonly string $apiKey = self::DEFAULT_SETTINGS['apiKey'],
        private readonly string $searchEngineEndpoint = self::DEFAULT_SETTINGS['searchEngineEndpoint'],
        private readonly bool $enableDebug = self::DEFAULT_SETTINGS['enableDebug'],
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
            apiKey: (string)$extensionSettings['apiKey'],
            searchEngineEndpoint: (string)$extensionSettings['searchEngineEndpoint'],
            enableDebug: (bool)$extensionSettings['enableDebug'],
            notifyBatchMode: (bool)$extensionSettings['notifyBatchMode'],
        );
    }

    /**
     * @throws ApiKeyNotAvailableException
     */
    public function getApiKey(): string
    {
        if ($this->apiKey === '') {
            throw new ApiKeyNotAvailableException(
                'API key for indexnow not set in extension settings',
                1636752398
            );
        }

        return $this->apiKey;
    }

    public function getSearchEngineEndpoint(): string
    {
        if ($this->searchEngineEndpoint === '') {
            return 'https://www.bing.com/indexnow?url=###URL###&key=###APIKEY###';
        }

        return $this->searchEngineEndpoint;
    }

    public function isEnableDebug(): bool
    {
        return $this->enableDebug;
    }

    public function isNotifyBatchMode(): bool
    {
        return (bool)$this->notifyBatchMode;
    }
}
