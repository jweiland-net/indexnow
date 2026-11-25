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
 * Immutable representation of the extension settings (Settings → Extension Configuration)
 */
#[Autoconfigure(constructor: 'create')]
final readonly class ExtConf
{
    private const EXT_KEY = 'indexnow';

    private const DEFAULT_SEARCH_ENGINE_ENDPOINT = 'https://www.bing.com/indexnow';

    public function __construct(
        private string $searchEngineEndpoint = '',
        private bool $notifyBatchMode = false,
    ) {}

    public static function create(ExtensionConfiguration $extensionConfiguration): self
    {
        try {
            $settings = $extensionConfiguration->get(self::EXT_KEY);
        } catch (ExtensionConfigurationExtensionNotConfiguredException|ExtensionConfigurationPathDoesNotExistException) {
            return new self();
        }

        if (!is_array($settings)) {
            return new self();
        }

        $searchEngineEndpoint = $settings['searchEngineEndpoint'] ?? '';
        $notifyBatchMode = $settings['notifyBatchMode'] ?? false;

        return new self(
            searchEngineEndpoint: is_string($searchEngineEndpoint) ? trim($searchEngineEndpoint) : '',
            notifyBatchMode: is_scalar($notifyBatchMode) && (bool)$notifyBatchMode,
        );
    }

    public function getSearchEngineEndpoint(): string
    {
        return $this->searchEngineEndpoint === '' ? self::DEFAULT_SEARCH_ENGINE_ENDPOINT : $this->searchEngineEndpoint;
    }

    public function isNotifyBatchMode(): bool
    {
        return $this->notifyBatchMode;
    }
}
