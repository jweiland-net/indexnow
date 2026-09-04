<?php

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\IndexNow\Tests\Functional\Hook;

use JWeiland\IndexNow\Domain\Repository\StackRepository;
use JWeiland\IndexNow\Event\ModifyPageUidEvent;
use JWeiland\IndexNow\Event\ProvidePreviewUrlEvent;
use JWeiland\IndexNow\Hook\DataHandlerHook;
use JWeiland\IndexNow\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case
 */
class DataHandlerHookTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    public DataHandlerHook $subject;

    public StackRepository|MockObject $stackRepositoryMock;

    protected array $testExtensionsToLoad = [
        'jweiland/indexnow',
    ];

    protected const LANGUAGE_PRESETS = [
        'EN' => [
            'id' => 0,
            'title' => 'English',
            'locale' => 'en_US.UTF8',
            'iso' => 'en',
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/tx_indexnow_stack.csv');

        $GLOBALS['BE_USER'] = new BackendUserAuthentication();
        $GLOBALS['BE_USER']->workspace = 0;
        $GLOBALS['BE_USER']->user = [
            'uid' => 1,
            'username' => 'admin',
            'admin' => 1,
        ];

        $request = new ServerRequest('https://www.example.com/typo3', 'GET');
        $request = $request->withQueryParams(
            [
                'overrideVals' => [
                    'pages' => [
                        'sys_language_uid' => 0,
                    ],
                ],
            ],
        );
        $GLOBALS['TYPO3_REQUEST'] = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);

        $this->writeSiteConfiguration(
            'indexnow-test',
            $this->buildSiteConfiguration(1, 'https://example.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', 'https://example.com/'),
            ],
        );

        $this->stackRepositoryMock = $this->createMock(StackRepository::class);

        $this->subject = new DataHandlerHook(
            $this->stackRepositoryMock,
            $this->get(PageRenderer::class),
            $this->get(FlashMessageService::class),
            $this->get(EventDispatcher::class),
        );
    }

    protected function tearDown(): void
    {
        unset(
            $this->subject,
        );

        parent::tearDown();
    }

    #[Test]
    public function hookWillNotBeProcessedOnEmptyDataMap(): void
    {
        $this->stackRepositoryMock
            ->expects(self::never())
            ->method('insert');

        /** @var DataHandler $dataHandler */
        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->datamap = [];

        $this->subject->processDatamap_beforeStart($dataHandler);
    }

    #[Test]
    public function hookWillNotBeProcessedOnEmptyTableData(): void
    {
        $this->stackRepositoryMock
            ->expects(self::never())
            ->method('insert');

        /** @var DataHandler $dataHandler */
        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->datamap = [
            'pages' => [
                'NEW1234' => [],
            ],
        ];

        $this->subject->processDatamap_beforeStart($dataHandler);
    }

    #[Test]
    public function hookWillNotBeProcessedOnPageRecordWithoutUid(): void
    {
        $this->stackRepositoryMock
            ->expects(self::never())
            ->method('insert');

        /** @var DataHandler $dataHandler */
        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->datamap = [
            'pages' => [
                'NEW1234' => [
                    'pid' => 1,
                    'title' => 'Plugin: maps2',
                ],
            ],
        ];

        $this->subject->processDatamap_beforeStart($dataHandler);
    }

    #[Test]
    public function hookWillNotBeProcessedWithoutBackendUser(): void
    {
        $this->stackRepositoryMock
            ->expects(self::never())
            ->method('insert');

        unset($GLOBALS['BE_USER']);

        /** @var DataHandler $dataHandler */
        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->datamap = [
            'pages' => [
                'NEW1234' => [
                    'uid' => 2,
                    'pid' => 1,
                    'title' => 'Plugin: events2',
                ],
            ],
        ];

        $this->subject->processDatamap_beforeStart($dataHandler);
    }

    #[Test]
    public function hookWillNotBeProcessedInNonLiveWorkspace(): void
    {
        $this->stackRepositoryMock
            ->expects(self::never())
            ->method('insert');

        $GLOBALS['BE_USER']->workspace = 1;

        /** @var DataHandler $dataHandler */
        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->datamap = [
            'pages' => [
                'NEW1234' => [
                    'uid' => 2,
                    'pid' => 1,
                    'title' => 'Plugin: events2',
                ],
            ],
        ];

        $this->subject->processDatamap_beforeStart($dataHandler);
    }

    #[Test]
    public function hookWillNotBeProcessedWithoutAuthenticatedUser(): void
    {
        $this->stackRepositoryMock
            ->expects(self::never())
            ->method('insert');

        $GLOBALS['BE_USER']->user = [];

        /** @var DataHandler $dataHandler */
        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->datamap = [
            'pages' => [
                'NEW1234' => [
                    'uid' => 2,
                    'pid' => 1,
                    'title' => 'Plugin: events2',
                ],
            ],
        ];

        $this->subject->processDatamap_beforeStart($dataHandler);
    }

    #[Test]
    public function hookWillInsertNewStackRecord(): void
    {
        // PreviewUriBuilder::create is called statically in DataHandlerHook
        // so we have to initialize the full TYPO3 backend and site
        $this->stackRepositoryMock
            ->expects(self::atLeastOnce())
            ->method('insert')
            ->with(self::identicalTo('https://example.com/'), self::identicalTo(2));

        /** @var DataHandler $dataHandler */
        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->datamap = [
            'pages' => [
                'NEW1234' => [
                    'uid' => 2,
                    'pid' => 1,
                    'title' => 'Plugin: events2',
                ],
            ],
        ];

        $this->subject->processDatamap_beforeStart($dataHandler);
    }

    #[Test]
    public function hookWillInsertNewStackRecordWithPreviewUrlFromEventListener(): void
    {
        $modifyPageUidEvent = new ModifyPageUidEvent(
            [],
            'pages',
            16,
            [],
        );

        $providePreviewUrlEvent = new ProvidePreviewUrlEvent(
            table: 'tx_news_domain_model_news',
            recordUid: 123,
            record: [
                'uid' => 123,
                'pid' => 16,
            ],
        );

        $providePreviewUrlEvent->addPreviewUrl('https://example.com/news/123');

        $eventDispatcherMock = $this->createMock(EventDispatcher::class);
        $eventDispatcherMock
            ->expects(self::atLeastOnce())
            ->method('dispatch')
            ->willReturnCallback(static function (object $event) use ($providePreviewUrlEvent, $modifyPageUidEvent): object {
                if ($event instanceof ProvidePreviewUrlEvent) {
                    return $providePreviewUrlEvent;
                }

                if ($event instanceof ModifyPageUidEvent) {
                    return $modifyPageUidEvent;
                }

                return $event;
            });

        $subject = new DataHandlerHook(
            $this->stackRepositoryMock,
            $this->get(PageRenderer::class),
            $this->get(FlashMessageService::class),
            $eventDispatcherMock,
        );

        // PreviewUriBuilder::create is called statically in DataHandlerHook
        // so we have to initialize the full TYPO3 backend and site
        $this->stackRepositoryMock
            ->expects(self::atLeastOnce())
            ->method('insert')
            ->with(self::identicalTo('https://example.com/news/123'), self::identicalTo(16));

        /** @var DataHandler $dataHandler */
        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->datamap = [
            'pages' => [
                'NEW1234' => [
                    'uid' => 2,
                    'pid' => 1,
                    'title' => 'Plugin: events2',
                ],
            ],
        ];

        $subject->processDatamap_beforeStart($dataHandler);
    }

    #[Test]
    public function hookWillInsertMultipleStackRecordsForRecordRenderedOnSeveralPages(): void
    {
        $modifyPageUidEvent = new ModifyPageUidEvent(
            [],
            'pages',
            16,
            [],
        );

        $providePreviewUrlEvent = new ProvidePreviewUrlEvent(
            table: 'tx_faq_domain_model_question',
            recordUid: 123,
            record: [
                'uid' => 123,
                'pid' => 16,
            ],
        );

        // Same FAQ record rendered on two different pages/sites.
        $providePreviewUrlEvent->addPreviewUrl('https://example.com/faq/123');
        $providePreviewUrlEvent->addPreviewUrl('https://other-site.com/faq/123', 42);

        $eventDispatcherMock = $this->createMock(EventDispatcher::class);
        $eventDispatcherMock
            ->expects(self::atLeastOnce())
            ->method('dispatch')
            ->willReturnCallback(static function (object $event) use ($providePreviewUrlEvent, $modifyPageUidEvent): object {
                if ($event instanceof ProvidePreviewUrlEvent) {
                    return $providePreviewUrlEvent;
                }

                if ($event instanceof ModifyPageUidEvent) {
                    return $modifyPageUidEvent;
                }

                return $event;
            });

        $subject = new DataHandlerHook(
            $this->stackRepositoryMock,
            $this->get(PageRenderer::class),
            $this->get(FlashMessageService::class),
            $eventDispatcherMock,
        );

        $insertedUrls = [];
        $this->stackRepositoryMock
            ->expects(self::exactly(2))
            ->method('insert')
            ->willReturnCallback(static function (string $url, int $pageUid) use (&$insertedUrls): void {
                $insertedUrls[] = [$url, $pageUid];
            });

        /** @var DataHandler $dataHandler */
        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->datamap = [
            'pages' => [
                'NEW1234' => [
                    'uid' => 2,
                    'pid' => 1,
                    'title' => 'Plugin: events2',
                ],
            ],
        ];

        $subject->processDatamap_beforeStart($dataHandler);

        self::assertSame(
            [
                ['https://example.com/faq/123', 16],
                ['https://other-site.com/faq/123', 42],
            ],
            $insertedUrls,
        );
    }
}
