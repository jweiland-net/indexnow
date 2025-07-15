<?php

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\IndexNow\Tests\Functional\Configuration;

use JWeiland\IndexNow\Configuration\Exception\ApiKeyNotAvailableException;
use JWeiland\Indexnow\Configuration\ExtConf;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case
 */
class ExtConfTest extends FunctionalTestCase
{
    public ExtensionConfiguration|MockObject $extensionConfigurationMock;

    protected array $testExtensionsToLoad = [
        'jweiland/indexnow',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->extensionConfigurationMock = $this->createMock(ExtensionConfiguration::class);
    }

    protected function tearDown(): void
    {
        unset(
            $this->extensionConfigurationMock,
        );

        parent::tearDown();
    }

    #[Test]
    public function getApiKeyInitiallyResultsInException(): void
    {
        $this->expectException(ApiKeyNotAvailableException::class);

        $config = [];
        $subject = new ExtConf(...$config);

        $subject->getApiKey();
    }

    #[Test]
    public function setApiKeySetsApiKey(): void
    {
        $config = [
            'apiKey' => 'foo bar',
        ];
        $subject = new ExtConf(...$config);

        self::assertSame(
            'foo bar',
            $subject->getApiKey(),
        );
    }

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
            'notifyBatchMode' => '1',
        ];
        $subject = new ExtConf(...$config);

        self::assertTrue(
            $subject->isNotifyBatchMode(),
        );
    }
}
