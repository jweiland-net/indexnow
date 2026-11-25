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
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteSettings;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ApiKeyResolverTest extends UnitTestCase
{
    private SiteFinder&MockObject $siteFinderMock;

    private ApiKeyResolver $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->siteFinderMock = $this->createMock(SiteFinder::class);
        $this->subject = new ApiKeyResolver($this->siteFinderMock);
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
        $this->siteFinderMock->method('getSiteByPageId')->with(10)->willReturn($this->makeSite('my-api-key'));

        self::assertSame('my-api-key', $this->subject->getApiKeyForPage(10));
    }

    #[Test]
    public function getApiKeyForPageThrowsExceptionIfSiteNotFound(): void
    {
        $this->siteFinderMock->method('getSiteByPageId')->willThrowException(new SiteNotFoundException('', 1));

        $this->expectException(ApiKeyNotAvailableException::class);
        $this->expectExceptionCode(1636752398);

        $this->subject->getApiKeyForPage(10);
    }

    #[Test]
    public function getApiKeyForPageThrowsExceptionIfApiKeyIsEmpty(): void
    {
        $this->siteFinderMock->method('getSiteByPageId')->willReturn($this->makeSite(''));

        $this->expectException(ApiKeyNotAvailableException::class);
        $this->expectExceptionCode(1636752399);

        $this->subject->getApiKeyForPage(10);
    }
}
