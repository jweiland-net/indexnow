<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\IndexNow\Tests\Functional\Service;

use JWeiland\IndexNow\Service\RecordResolver;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class RecordResolverTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'jweiland/indexnow',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
    }

    private function createSubject(): RecordResolver
    {
        return new RecordResolver($this->get(TcaSchemaFactory::class));
    }

    #[Test]
    public function resolveReturnsRecordFromRequestForNewRecords(): void
    {
        self::assertSame(
            ['pid' => 1, 'title' => 'New'],
            $this->createSubject()->resolve('pages', 'NEW1234', ['pid' => 1, 'title' => 'New']),
        );
    }

    #[Test]
    public function resolveMergesPersistedUidAndPidIntoRecordFromRequest(): void
    {
        $record = $this->createSubject()->resolve('pages', 2, ['title' => 'Changed']);

        self::assertSame(2, (int)$record['uid']);
        self::assertSame(1, (int)$record['pid']);
        self::assertSame('Changed', $record['title']);
    }

    #[Test]
    public function resolveReturnsRecordFromRequestIfPersistedRecordDoesNotExist(): void
    {
        self::assertSame(
            ['title' => 'Changed'],
            $this->createSubject()->resolve('pages', 999, ['title' => 'Changed']),
        );
    }

    #[Test]
    public function resolveAddsPersistedLanguageOfTranslation(): void
    {
        $this->getConnectionPool()->getConnectionForTable('pages')->insert('pages', [
            'uid' => 3,
            'pid' => 1,
            'title' => 'Unterseite',
            'sys_language_uid' => 1,
            'l10n_parent' => 2,
        ]);

        // Translations synchronized by TYPO3's DataMapProcessor only contain the synchronized fields
        $record = $this->createSubject()->resolve('pages', 3, ['layout' => 1]);

        self::assertSame(1, (int)$record['sys_language_uid']);
        self::assertSame(1, $record['layout']);
    }
}
