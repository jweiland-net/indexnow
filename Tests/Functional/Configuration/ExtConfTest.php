<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\IndexNow\Tests\Functional\Configuration;

use JWeiland\IndexNow\Configuration\ExtConf;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case
 */
class ExtConfTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'jweiland/indexnow',
    ];

    #[Test]
    public function getSearchEngineEndpointInitiallyReturnsBingSearchEngine(): void
    {
        $config = [];
        $subject = new ExtConf(...$config);

        self::assertSame(
            'https://www.bing.com/indexnow',
            $subject->getSearchEngineEndpoint(),
        );
    }

    #[Test]
    public function setSearchEngineEndpointSetsSearchEngineEndpoint(): void
    {
        $config = [
            'searchEngineEndpoint' => 'https://example.com',
        ];
        $subject = new ExtConf(...$config);

        self::assertSame(
            'https://example.com',
            $subject->getSearchEngineEndpoint(),
        );
    }

    #[Test]
    public function getNotifyBatchModeInitiallyReturnsFalse(): void
    {
        $config = [];
        $subject = new ExtConf(...$config);

        self::assertFalse(
            $subject->isNotifyBatchMode(),
        );
    }

    #[Test]
    public function setNotifyBatchModeSetsNotifyBatchMode(): void
    {
        $config = [
            'notifyBatchMode' => true,
        ];
        $subject = new ExtConf(...$config);

        self::assertTrue(
            $subject->isNotifyBatchMode(),
        );
    }
}
