<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\IndexNow\Tests\Unit\Configuration;

use JWeiland\IndexNow\Configuration\ApiKeyResolver;
use JWeiland\IndexNow\Configuration\Exception\ApiKeyNotAvailableException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Stub;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteSettings;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ApiKeyResolverTest extends UnitTestCase
{
    private SiteFinder&Stub $siteFinderStub;

    private ApiKeyResolver $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->siteFinderStub = self::createStub(SiteFinder::class);
        $this->subject = new ApiKeyResolver($this->siteFinderStub);
    }

    private function makeSite(string $apiKey): Site
    {
        return new Site(
            'test',
            1,
            ['base' => 'https://example.com/'],
            SiteSettings::createFromSettingsTree(['indexnow' => ['apiKey' => $apiKey]]),
        );
    }

    #[Test]
    public function getApiKeyForSiteReturnsEmptyStringForNullSite(): void
    {
        self::assertSame('', $this->subject->getApiKeyForSite(new NullSite()));
    }

    #[Test]
    public function getApiKeyForSiteReturnsTrimmedApiKey(): void
    {
        self::assertSame('my-api-key', $this->subject->getApiKeyForSite($this->makeSite(' my-api-key ')));
    }

    #[Test]
    public function getApiKeyForPageReturnsApiKey(): void
    {
        $this->siteFinderStub->method('getSiteByPageId')->willReturnMap([[10, $this->makeSite('my-api-key')]]);

        self::assertSame('my-api-key', $this->subject->getApiKeyForPage(10));
    }

    #[Test]
    public function getApiKeyForPageThrowsExceptionIfSiteNotFound(): void
    {
        $this->siteFinderStub->method('getSiteByPageId')->willThrowException(new SiteNotFoundException('', 1));

        $this->expectException(ApiKeyNotAvailableException::class);
        $this->expectExceptionCode(1636752398);

        $this->subject->getApiKeyForPage(10);
    }

    #[Test]
    public function getApiKeyForPageThrowsExceptionIfApiKeyIsEmpty(): void
    {
        $this->siteFinderStub->method('getSiteByPageId')->willReturn($this->makeSite(''));

        $this->expectException(ApiKeyNotAvailableException::class);
        $this->expectExceptionCode(1636752399);

        $this->subject->getApiKeyForPage(10);
    }
}
