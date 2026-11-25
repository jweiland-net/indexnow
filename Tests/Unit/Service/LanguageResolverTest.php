<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\IndexNow\Tests\Unit\Service;

use JWeiland\IndexNow\Service\LanguageResolver;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class LanguageResolverTest extends UnitTestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TYPO3_REQUEST']);

        parent::tearDown();
    }

    #[Test]
    public function resolveReturnsLanguageOfRecord(): void
    {
        self::assertSame(2, (new LanguageResolver())->resolve('tt_content', ['sys_language_uid' => 2]));
    }

    #[Test]
    public function resolveReturnsZeroForInvalidLanguage(): void
    {
        self::assertSame(0, (new LanguageResolver())->resolve('tt_content', ['sys_language_uid' => -1]));
    }

    #[Test]
    public function resolveForPagesPrefersLanguageFromRequest(): void
    {
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest('https://example.com/typo3'))
            ->withQueryParams(['overrideVals' => ['pages' => ['sys_language_uid' => '3']]]);

        self::assertSame(3, (new LanguageResolver())->resolve('pages', ['sys_language_uid' => 1]));
    }

    #[Test]
    public function resolveForPagesFallsBackToLanguageOfRecord(): void
    {
        $GLOBALS['TYPO3_REQUEST'] = new ServerRequest('https://example.com/typo3');

        self::assertSame(1, (new LanguageResolver())->resolve('pages', ['sys_language_uid' => 1]));
    }

    #[Test]
    public function resolveForPagesIgnoresInvalidOverrideValues(): void
    {
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest('https://example.com/typo3'))
            ->withQueryParams(['overrideVals' => ['pages' => 'invalid']]);

        self::assertSame(1, (new LanguageResolver())->resolve('pages', ['sys_language_uid' => 1]));
    }

    #[Test]
    public function resolveForPagesIgnoresMissingRequest(): void
    {
        self::assertSame(1, (new LanguageResolver())->resolve('pages', ['sys_language_uid' => 1]));
    }
}
