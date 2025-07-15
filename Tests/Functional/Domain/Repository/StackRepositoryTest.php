<?php

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Indexnow\Tests\Functional\Domain\Repository;

use JWeiland\IndexNow\Domain\Model\Stack;
use JWeiland\IndexNow\Domain\Repository\StackRepository;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case
 */
class StackRepositoryTest extends FunctionalTestCase
{
    public StackRepository $subject;

    protected array $testExtensionsToLoad = [
        'jweiland/indexnow',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/tx_indexnow_stack.csv');

        $this->subject = $this->get(StackRepository::class);
    }

    protected function tearDown(): void
    {
        unset(
            $this->subject,
        );

        parent::tearDown();
    }

    #[Test]
    public function findAllReturnsStacksAsObjectStorage(): void
    {
        $stackRecords = $this->subject->findAll();

        reset($stackRecords);

        self::assertInstanceOf(
            Stack::class,
            current($stackRecords)
        );

        self::assertCount(
            3,
            $stackRecords,
        );
    }

    #[Test]
    public function deleteByUidWillDeleteOneStackRecord(): void
    {
        $this->subject->deleteByUid(2);

        $connection = $this->getConnectionPool()->getConnectionForTable(StackRepository::TABLE);
        $numberOfRecords = $connection->count(
            '*',
            StackRepository::TABLE,
            [],
        );

        self::assertSame(
            2,
            $numberOfRecords,
        );
    }

    #[Test]
    public function insertWillNotInsertDuplicateStackRecord(): void
    {
        $this->subject->insert('https://example.com/');

        $connection = $this->getConnectionPool()->getConnectionForTable(StackRepository::TABLE);
        $numberOfRecords = $connection->count(
            '*',
            StackRepository::TABLE,
            [],
        );

        self::assertSame(
            3,
            $numberOfRecords,
        );
    }

    #[Test]
    public function insertWillInsertStackRecord(): void
    {
        $this->subject->insert('https://typo3.com/');

        $connection = $this->getConnectionPool()->getConnectionForTable(StackRepository::TABLE);
        $numberOfRecords = $connection->count(
            '*',
            StackRepository::TABLE,
            [],
        );

        self::assertSame(
            4,
            $numberOfRecords,
        );
    }
}
