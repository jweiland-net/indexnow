<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\IndexNow\Configuration;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\SingletonInterface;

/*
 * This class streamlines all settings from extension manager
 */
class ExtConf implements SingletonInterface
{
    /**
     * @var string
     */
    protected $apiKey = '';

    /**
     * @var string
     */
    protected $searchEngineEndpoint = '';

    public function __construct(ExtensionConfiguration $extensionConfiguration)
    {
        $extConf = $extensionConfiguration->get('indexnow');
        if (is_array($extConf)) {
            // call setter method foreach configuration entry
            foreach ($extConf as $key => $value) {
                $methodName = 'set' . ucfirst($key);
                if (method_exists($this, $methodName)) {
                    $this->$methodName($value);
                }
            }
        }
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function setApiKey(string $apiKey): void
    {
        $this->apiKey = trim($apiKey);
    }

    public function getSearchEngineEndpoint(): string
    {
        if ($this->searchEngineEndpoint === '') {
            return 'https://www.bing.com/indexnow?url=###URL###&key=###APIKEY###';
        }

        return $this->searchEngineEndpoint;
    }

    public function setSearchEngineEndpoint(string $searchEngineEndpoint): void
    {
        $this->searchEngineEndpoint = trim($searchEngineEndpoint);
    }
}
