<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\IndexNow\Tests\Functional\Service;

use JWeiland\IndexNow\Event\ModifyPageUidEvent;
use JWeiland\IndexNow\Service\PageUidResolver;
use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PageUidResolverTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'jweiland/indexnow',
    ];

    private ?ModifyPageUidEvent $dispatchedEvent = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
    }

    private function createSubject(): PageUidResolver
    {
        $eventDispatcher = new class ($this) implements EventDispatcherInterface {
            public function __construct(private readonly PageUidResolverTest $testCase) {}

            public function dispatch(object $event): object
            {
                if ($event instanceof ModifyPageUidEvent) {
                    $this->testCase->captureEvent($event);
                }

                return $event;
            }
        };

        return new PageUidResolver($eventDispatcher);
    }

    public function captureEvent(ModifyPageUidEvent $event): void
    {
        $this->dispatchedEvent = $event;
    }

    #[Test]
    public function resolveReturnsUidOfPage(): void
    {
        self::assertSame(2, $this->createSubject()->resolve('pages', ['uid' => 2, 'pid' => 1]));
    }

    #[Test]
    public function resolveReturnsPidOfContentRecord(): void
    {
        self::assertSame(2, $this->createSubject()->resolve('tt_content', ['uid' => 10, 'pid' => 2]));
    }

    #[Test]
    public function resolveReturnsZeroWithoutUidOrPid(): void
    {
        self::assertSame(0, $this->createSubject()->resolve('pages', ['pid' => 1, 'title' => 'New']));
    }

    #[Test]
    public function resolveProvidesFullPersistedPageRecordToListeners(): void
    {
        // DataHandler only passes the changed fields; listeners (f.e. for "no_index") need the full page record
        $this->createSubject()->resolve('pages', ['uid' => 2, 'pid' => 1, 'layout' => 1]);

        self::assertNotNull($this->dispatchedEvent);
        self::assertSame('Plugin: maps2', $this->dispatchedEvent->getPageRecord()['title'] ?? null);
        self::assertSame(['uid' => 2, 'pid' => 1, 'layout' => 1], $this->dispatchedEvent->getRecord());
    }

    #[Test]
    public function resolveProvidesPageRecordOfContentRecordToListeners(): void
    {
        $this->createSubject()->resolve('tt_content', ['uid' => 10, 'pid' => 1]);

        self::assertSame('Home', $this->dispatchedEvent?->getPageRecord()['title'] ?? null);
    }
}
