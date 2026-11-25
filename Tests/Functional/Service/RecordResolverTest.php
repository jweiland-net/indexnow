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

    #[Test]
    public function resolveReturnsRecordFromRequestForNewRecords(): void
    {
        self::assertSame(
            ['pid' => 1, 'title' => 'New'],
            (new RecordResolver())->resolve('pages', 'NEW1234', ['pid' => 1, 'title' => 'New']),
        );
    }

    #[Test]
    public function resolveMergesPersistedUidAndPidIntoRecordFromRequest(): void
    {
        $record = (new RecordResolver())->resolve('pages', 2, ['title' => 'Changed']);

        self::assertSame(2, (int)$record['uid']);
        self::assertSame(1, (int)$record['pid']);
        self::assertSame('Changed', $record['title']);
    }

    #[Test]
    public function resolveReturnsRecordFromRequestIfPersistedRecordDoesNotExist(): void
    {
        self::assertSame(
            ['title' => 'Changed'],
            (new RecordResolver())->resolve('pages', 999, ['title' => 'Changed']),
        );
    }
}
