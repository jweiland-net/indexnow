<?php

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\IndexNow\Tests\Functional\Event;

use JWeiland\IndexNow\Event\ModifyPageUidEvent;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case
 */
class ModifyPageUidEventTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'jweiland/indexnow',
    ];

    #[Test]
    public function getRecordWillReturnRecord(): void
    {
        $subject = new ModifyPageUidEvent(
            [
                'uid' => 123,
                'pid' => 1,
                'header' => 'Plugin: maps2',
            ],
            'tt_content',
            1,
            [
                'uid' => 1,
                'pid' => 0,
                'title' => 'Home',
            ],
        );

        self::assertSame(
            [
                'uid' => 123,
                'pid' => 1,
                'header' => 'Plugin: maps2',
            ],
            $subject->getRecord(),
        );
    }

    #[Test]
    public function getTableWillReturnTable(): void
    {
        $subject = new ModifyPageUidEvent(
            [
                'uid' => 123,
                'pid' => 1,
                'header' => 'Plugin: maps2',
            ],
            'tt_content',
            1,
            [
                'uid' => 1,
                'pid' => 0,
                'title' => 'Home',
            ],
        );

        self::assertSame(
            'tt_content',
            $subject->getTable(),
        );
    }

    #[Test]
    public function getPageUidWillReturnPageUid(): void
    {
        $subject = new ModifyPageUidEvent(
            [
                'uid' => 123,
                'pid' => 1,
                'header' => 'Plugin: maps2',
            ],
            'tt_content',
            1,
            [
                'uid' => 1,
                'pid' => 0,
                'title' => 'Home',
            ],
        );

        self::assertSame(
            1,
            $subject->getPageUid(),
        );
    }

    #[Test]
    public function getPageRecordWillReturnPageRecord(): void
    {
        $subject = new ModifyPageUidEvent(
            [
                'uid' => 123,
                'pid' => 1,
                'header' => 'Plugin: maps2',
            ],
            'tt_content',
            1,
            [
                'uid' => 1,
                'pid' => 0,
                'title' => 'Home',
            ],
        );

        self::assertSame(
            [
                'uid' => 1,
                'pid' => 0,
                'title' => 'Home',
            ],
            $subject->getPageRecord(),
        );
    }

    #[Test]
    public function setPageUidWillSetPageUid(): void
    {
        $subject = new ModifyPageUidEvent(
            [
                'uid' => 123,
                'pid' => 1,
                'header' => 'Plugin: maps2',
            ],
            'tt_content',
            1,
            [
                'uid' => 1,
                'pid' => 0,
                'title' => 'Home',
            ],
        );

        $subject->setPageUid(2);

        self::assertSame(
            2,
            $subject->getPageUid(),
        );
    }
}
