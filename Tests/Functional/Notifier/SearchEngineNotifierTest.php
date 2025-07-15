<?php

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\IndexNow\Tests\Functional\Notifier;

use JWeiland\IndexNow\Configuration\ExtConf;
use JWeiland\IndexNow\Domain\Model\Stack;
use JWeiland\IndexNow\Notifier\SearchEngineNotifier;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case
 */
class SearchEngineNotifierTest extends FunctionalTestCase
{
    public RequestFactory|MockObject $requestFactoryMock;

    public Logger|MockObject $loggerMock;

    protected array $testExtensionsToLoad = [
        'jweiland/indexnow',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->requestFactoryMock = $this->createMock(RequestFactory::class);
        $this->loggerMock = $this->createMock(Logger::class);
    }

    protected function tearDown(): void
    {
        unset(
            $this->requestFactoryMock,
            $this->loggerMock,
        );

        parent::tearDown();
    }

    #[Test]
    public function notifyWillLogInfoOnEmptyStorage(): void
    {
        $this->loggerMock
            ->expects(self::atLeastOnce())
            ->method('info')
            ->with(self::identicalTo('No URL records available, nothing sent.'));

        $subject = new SearchEngineNotifier(
            $this->requestFactoryMock,
            new ExtConf(),
            $this->loggerMock,
        );

        $subject->notify(new \SplObjectStorage());
    }

    #[Test]
    public function notifyWithDeactivatedBatchModeWillNotifySingleStack(): void
    {
        $this->loggerMock
            ->expects(self::atLeastOnce())
            ->method('info')
            ->willReturnMap([
                [self::identicalTo('Preparing batch for example.com with 2 URL(s)'), null],
                [self::identicalTo('Preparing batch for typo3.org with 1 URL(s)'), null],
            ]);

        $response = new Response();

        $this->requestFactoryMock
            ->expects(self::atLeastOnce())
            ->method('request')
            ->willReturnMap([
                ['https://www.bing.com/indexnow?url=https%3A%2F%2Fexample.com%2Fevents2&key=SuperSafeApiKey', $response],
                ['https://www.bing.com/indexnow?url=https%3A%2F%2Fexample.com%2Fmaps2&key=SuperSafeApiKey', $response],
                ['https://www.bing.com/indexnow?url=https%3A%2F%2Ftypo3.org%2F&key=SuperSafeApiKey', $response],
            ]);

        $subject = new SearchEngineNotifier(
            $this->requestFactoryMock,
            new ExtConf(
                apiKey: 'SuperSafeApiKey',
                notifyBatchMode: false,
            ),
            $this->loggerMock,
        );

        $stackStorage = new \SplObjectStorage();
        $stackStorage->attach(new Stack(1, 'https://example.com/events2'));
        $stackStorage->attach(new Stack(2, 'https://example.com/maps2'));
        $stackStorage->attach(new Stack(3, 'https://typo3.org/'));

        self::assertTrue(
            $subject->notify($stackStorage),
        );
    }

    #[Test]
    public function notifyWithActivatedBatchModeWillNotifySingleStack(): void
    {
        $this->loggerMock
            ->expects(self::atLeastOnce())
            ->method('info')
            ->willReturnMap([
                [self::identicalTo('Preparing batch for example.com with 1 URL(s)'), null],
                [self::identicalTo('Preparing batch for typo3.org with 1 URL(s)'), null],
            ]);

        $response = new Response();

        $this->requestFactoryMock
            ->expects(self::atLeastOnce())
            ->method('request')
            ->willReturnMap([
                ['https://www.bing.com/indexnow?url=https%3A%2F%2Fexample.com%2Fevents2&key=SuperSafeApiKey', $response],
                ['https://www.bing.com/indexnow?url=https%3A%2F%2Ftypo3.org%2F&key=SuperSafeApiKey', $response],
            ]);

        $subject = new SearchEngineNotifier(
            $this->requestFactoryMock,
            new ExtConf(
                apiKey: 'SuperSafeApiKey',
                notifyBatchMode: true,
            ),
            $this->loggerMock,
        );

        $stackStorage = new \SplObjectStorage();
        $stackStorage->attach(new Stack(1, 'https://example.com/events2'));
        $stackStorage->attach(new Stack(3, 'https://typo3.org/'));

        self::assertTrue(
            $subject->notify($stackStorage),
        );
    }

    #[Test]
    public function notifyWithActivatedBatchModeWillNotifyGroupedStacks(): void
    {
        $this->loggerMock
            ->expects(self::atLeastOnce())
            ->method('info')
            ->willReturnMap([
                [self::identicalTo('Preparing batch for example.com with 3 URL(s)'), null],
            ]);

        $postData = [
            'host' => 'example.com',
            'key' => 'SuperSafeApiKey',
            'keyLocation' => 'https://example.com/SuperSafeApiKey.txt',
            'urlList' => [
                'https://example.com/events2',
                'https://example.com/maps2',
                'https://example.com/indexnow',
            ],
        ];

        $this->requestFactoryMock
            ->expects(self::atLeastOnce())
            ->method('request')
            ->with(
                self::identicalTo('https://www.bing.com/indexnow'),
                self::identicalTo('POST'),
                self::identicalTo([
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'body' => json_encode($postData),
                ]),
            );

        $subject = new SearchEngineNotifier(
            $this->requestFactoryMock,
            new ExtConf(
                apiKey: 'SuperSafeApiKey',
                notifyBatchMode: true,
            ),
            $this->loggerMock,
        );

        $stackStorage = new \SplObjectStorage();
        $stackStorage->attach(new Stack(1, 'https://example.com/events2'));
        $stackStorage->attach(new Stack(2, 'https://example.com/maps2'));
        $stackStorage->attach(new Stack(3, 'https://example.com/indexnow'));

        self::assertTrue(
            $subject->notify($stackStorage),
        );
    }
}
