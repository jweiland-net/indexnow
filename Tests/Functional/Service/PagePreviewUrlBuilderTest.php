<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\IndexNow\Tests\Functional\Service;

use JWeiland\IndexNow\Service\PagePreviewUrlBuilder;
use JWeiland\IndexNow\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PagePreviewUrlBuilderTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en'],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de'],
    ];

    protected array $testExtensionsToLoad = [
        'jweiland/indexnow',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');

        $GLOBALS['BE_USER'] = new BackendUserAuthentication();
        $GLOBALS['BE_USER']->user = ['uid' => 1, 'username' => 'admin', 'admin' => 1];
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest('https://example.com/typo3'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);

        $this->writeSiteConfiguration(
            'indexnow-test',
            $this->buildSiteConfiguration(1, 'https://example.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', 'https://example.com/'),
                $this->buildLanguageConfiguration('DE', 'https://example.com/de/'),
            ],
        );
    }

    #[Test]
    public function buildReturnsUrlOfPage(): void
    {
        self::assertSame('https://example.com/', (new PagePreviewUrlBuilder())->build(1));
    }

    #[Test]
    public function buildReturnsUrlOfPageInGivenLanguage(): void
    {
        self::assertSame('https://example.com/de/', (new PagePreviewUrlBuilder())->build(1, 1));
    }

    #[Test]
    public function buildReturnsNullForPageOutsideOfAnySite(): void
    {
        self::assertNull((new PagePreviewUrlBuilder())->build(999));
    }
}
