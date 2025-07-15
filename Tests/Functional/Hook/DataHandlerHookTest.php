<?php

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\IndexNow\Tests\Functional\Hook;

use JWeiland\IndexNow\Domain\Repository\StackRepository;
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
            'iso' => 'en'
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
        $request = $request->withQueryParams([
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
    public function hookWillInsertNewStackRecord(): void
    {
        // PreviewUriBuilder::create is called statically in DataHandlerHook
        // so we have to initialize the full TYPO3 backend and site
        $this->stackRepositoryMock
            ->expects(self::atLeastOnce())
            ->method('insert')
            ->with(self::identicalTo('https://example.com/'));

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
}
