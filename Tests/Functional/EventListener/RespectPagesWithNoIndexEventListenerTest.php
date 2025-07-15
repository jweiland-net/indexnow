<?php

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\IndexNow\Tests\Functional\EventListener;

use JWeiland\IndexNow\Event\ModifyPageUidEvent;
use JWeiland\IndexNow\EventListener\RespectPagesWithNoIndexEventListener;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case
 */
class RespectPagesWithNoIndexEventListenerTest extends FunctionalTestCase
{
    public RespectPagesWithNoIndexEventListener $subject;

    protected array $testExtensionsToLoad = [
        'jweiland/indexnow',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new RespectPagesWithNoIndexEventListener();
    }

    protected function tearDown(): void
    {
        unset(
            $this->subject,
        );

        parent::tearDown();
    }

    #[Test]
    public function invokeWithInvalidPageRecordWillNotChangePageUid(): void
    {
        $event = new ModifyPageUidEvent(
            [
                'uid' => 123,
                'pid' => 1,
                'header' => 'Plugin: maps2',
            ],
            'tt_content',
            1,
            null,
        );

        $this->subject->__invoke($event);

        self::assertSame(
            1,
            $event->getPageUid(),
        );
    }

    #[Test]
    public function invokeWithoutNoIndexColumnWillNotChangePageUid(): void
    {
        $event = new ModifyPageUidEvent(
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

        $this->subject->__invoke($event);

        self::assertSame(
            1,
            $event->getPageUid(),
        );
    }

    #[Test]
    public function invokeWithNoIndexSetToZeroWillNotChangePageUid(): void
    {
        $event = new ModifyPageUidEvent(
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
                'no_index' => 0,
            ],
        );

        $this->subject->__invoke($event);

        self::assertSame(
            1,
            $event->getPageUid(),
        );
    }

    #[Test]
    public function invokeWithNoIndexSetToOneWillChangePageUidToZero(): void
    {
        $event = new ModifyPageUidEvent(
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
                'no_index' => 1,
            ],
        );

        $this->subject->__invoke($event);

        self::assertSame(
            0,
            $event->getPageUid(),
        );
    }
}
