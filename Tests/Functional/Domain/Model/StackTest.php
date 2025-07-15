<?php

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\IndexNow\Tests\Functional\Domain\Model;

use JWeiland\IndexNow\Domain\Model\Stack;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case
 */
class StackTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'jweiland/indexnow',
    ];

    #[Test]
    public function getUidGetsUid(): void
    {
        $config = [
            'uid' => 123,
            'url' => 'https://example.com/',
        ];
        $subject = new Stack(...$config);

        self::assertSame(
            123,
            $subject->getUid(),
        );
    }

    #[Test]
    public function getUrlGetsUrl(): void
    {
        $config = [
            'uid' => 123,
            'url' => 'https://example.com/',
        ];
        $subject = new Stack(...$config);

        self::assertSame(
            'https://example.com/',
            $subject->getUrl(),
        );
    }

    #[Test]
    public function hasValidUrlWithInvalidUrlReturnsFalse(): void
    {
        $config = [
            'uid' => 123,
            'url' => 'typo3',
        ];
        $subject = new Stack(...$config);

        self::assertFalse(
            $subject->hasValidUrl(),
        );
    }

    #[Test]
    public function hasValidUrlWithEmptyUrlReturnsFalse(): void
    {
        $config = [
            'uid' => 123,
            'url' => '',
        ];
        $subject = new Stack(...$config);

        self::assertFalse(
            $subject->hasValidUrl(),
        );
    }

    #[Test]
    public function hasValidUrlReturnsTrue(): void
    {
        $config = [
            'uid' => 123,
            'url' => 'https://example.com/',
        ];
        $subject = new Stack(...$config);

        self::assertTrue(
            $subject->hasValidUrl(),
        );
    }

    #[Test]
    public function getHostWithInvalidUrlReturnsEmptyString(): void
    {
        $config = [
            'uid' => 123,
            'url' => 'foo',
        ];
        $subject = new Stack(...$config);

        self::assertSame(
            '',
            $subject->getHost(),
        );
    }

    #[Test]
    public function getHostReturnsHost(): void
    {
        $config = [
            'uid' => 123,
            'url' => 'https://example.com/',
        ];
        $subject = new Stack(...$config);

        self::assertSame(
            'example.com',
            $subject->getHost(),
        );
    }
}
