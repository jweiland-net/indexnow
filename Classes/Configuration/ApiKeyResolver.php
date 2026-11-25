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
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Site\SiteFinder;

/**
 * Single source of truth for reading the IndexNow API key from the site settings (setting "indexnow.apiKey")
 */
final readonly class ApiKeyResolver
{
    private const SETTINGS_KEY = 'indexnow.apiKey';

    public function __construct(
        private SiteFinder $siteFinder,
    ) {}

    /**
     * Returns an empty string, if the site does not provide an API key
     */
    public function getApiKeyForSite(SiteInterface $site): string
    {
        if (!$site instanceof Site) {
            return '';
        }

        $apiKey = $site->getSettings()->get(self::SETTINGS_KEY, '');

        return is_string($apiKey) ? trim($apiKey) : '';
    }

    /**
     * @throws ApiKeyNotAvailableException
     */
    public function getApiKeyForPage(int $pageUid): string
    {
        try {
            $site = $this->siteFinder->getSiteByPageId($pageUid);
        } catch (SiteNotFoundException $siteNotFoundException) {
            throw new ApiKeyNotAvailableException(
                sprintf('No site found for page "%d": %s', $pageUid, $siteNotFoundException->getMessage()),
                1636752398,
                $siteNotFoundException,
            );
        }

        $apiKey = $this->getApiKeyForSite($site);
        if ($apiKey === '') {
            throw new ApiKeyNotAvailableException(
                sprintf('API key for indexnow not set in settings of site "%s"', $site->getIdentifier()),
                1636752399,
            );
        }

        return $apiKey;
    }
}
