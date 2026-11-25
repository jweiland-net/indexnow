<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\IndexNow\Tests\Functional\Hook;

use JWeiland\IndexNow\Domain\Repository\StackRepository;
use JWeiland\IndexNow\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Integration test: a real DataHandler run triggers the registered hook, which stores into tx_indexnow_stack.
 *
 * @see https://github.com/jweiland-net/indexnow/issues/28
 */
final class DataHandlerHookIntegrationTest extends FunctionalTestCase
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

        $this->importCSVDataSet(__DIR__ . '/Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/pages_with_translation.csv');

        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest('https://example.com/typo3/'))
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

    /**
     * @param array<string, array<int|string, array<string, mixed>>> $dataMap
     */
    private function processDataMap(array $dataMap): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($dataMap, []);
        $dataHandler->process_datamap();

        self::assertSame([], $dataHandler->errorLog);
    }

    /**
     * @return list<string>
     */
    private function getQueuedUrls(): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable(StackRepository::TABLE);
        $urls = $queryBuilder
            ->select('url')
            ->from(StackRepository::TABLE)
            ->orderBy('url')
            ->executeQuery()
            ->fetchFirstColumn();

        return array_map(strval(...), $urls);
    }

    private function getPageField(int $uid, string $field): mixed
    {
        return $this->getConnectionPool()
            ->getConnectionForTable('pages')
            ->select([$field], 'pages', ['uid' => $uid])
            ->fetchOne();
    }

    #[Test]
    public function changingExcludedFieldOfDefaultLanguageAlsoQueuesTranslations(): void
    {
        $this->processDataMap(['pages' => [2 => ['layout' => 1]]]);

        // Precondition: TYPO3 synchronized the l10n_mode=exclude field into the translation
        self::assertSame(1, (int)$this->getPageField(3, 'layout'));

        self::assertSame(
            ['https://example.com/de/unterseite', 'https://example.com/sub'],
            $this->getQueuedUrls(),
        );
    }

    #[Test]
    public function changingTranslatableFieldOfDefaultLanguageOnlyQueuesDefaultLanguage(): void
    {
        $this->processDataMap(['pages' => [2 => ['title' => 'Changed sub page']]]);

        self::assertSame('Unterseite', $this->getPageField(3, 'title'));
        self::assertSame(['https://example.com/sub'], $this->getQueuedUrls());
    }

    #[Test]
    public function savingWithoutChangesQueuesNothing(): void
    {
        // Like FormEngine, submit all fields, including the MM relation field "categories"
        $this->processDataMap(['pages' => [2 => ['title' => 'Sub page', 'layout' => 0, 'categories' => '']]]);

        self::assertSame([], $this->getQueuedUrls());
    }

    #[Test]
    public function changingTranslationQueuesTranslatedUrl(): void
    {
        $this->processDataMap(['pages' => [3 => ['title' => 'Geänderte Unterseite']]]);

        self::assertSame(['https://example.com/de/unterseite'], $this->getQueuedUrls());
    }

    #[Test]
    public function creatingVisiblePageQueuesItsUrl(): void
    {
        $this->processDataMap(['pages' => ['NEW1' => ['pid' => 1, 'title' => 'New page', 'hidden' => 0]]]);

        self::assertSame(['https://example.com/new-page'], $this->getQueuedUrls());
    }

    #[Test]
    public function creatingVisiblePageWithSubPageInOneRequestQueuesBothUrls(): void
    {
        $this->processDataMap([
            'pages' => [
                'NEW1' => ['pid' => 1, 'title' => 'New page', 'hidden' => 0],
                'NEW2' => ['pid' => 'NEW1', 'title' => 'New sub page', 'hidden' => 0],
            ],
        ]);

        self::assertSame(
            ['https://example.com/new-page', 'https://example.com/new-page/new-sub-page'],
            $this->getQueuedUrls(),
        );
    }

    #[Test]
    public function creatingPageWithDefaultsQueuesNothingAsNewPagesAreHidden(): void
    {
        // TYPO3 creates new pages disabled by default (EXT:core/Configuration/TCA/Overrides/pages.php)
        $this->processDataMap(['pages' => ['NEW1' => ['pid' => 1, 'title' => 'New page']]]);

        self::assertSame([], $this->getQueuedUrls());
    }

    #[Test]
    public function hidingPageQueuesNothing(): void
    {
        $this->processDataMap(['pages' => [2 => ['hidden' => 1]]]);

        self::assertSame([], $this->getQueuedUrls());
    }

    #[Test]
    public function changingHiddenPageQueuesNothing(): void
    {
        $this->getConnectionPool()->getConnectionForTable('pages')->update('pages', ['hidden' => 1], ['uid' => 2]);

        $this->processDataMap(['pages' => [2 => ['title' => 'Changed sub page']]]);

        self::assertSame([], $this->getQueuedUrls());
    }

    #[Test]
    public function makingHiddenPageVisibleQueuesItsUrl(): void
    {
        $this->getConnectionPool()->getConnectionForTable('pages')->update('pages', ['hidden' => 1], ['uid' => 2]);

        $this->processDataMap(['pages' => [2 => ['hidden' => 0]]]);

        self::assertSame(['https://example.com/sub'], $this->getQueuedUrls());
    }

    #[Test]
    public function changingSlugQueuesTheNewUrl(): void
    {
        $this->processDataMap(['pages' => [2 => ['slug' => '/renamed']]]);

        self::assertSame(['https://example.com/renamed'], $this->getQueuedUrls());
    }
}
