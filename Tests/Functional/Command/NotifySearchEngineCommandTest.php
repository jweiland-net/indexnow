<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\IndexNow\Tests\Functional\Command;

use JWeiland\IndexNow\Command\NotifySearchEngineCommand;
use JWeiland\IndexNow\Domain\Model\Stack;
use JWeiland\IndexNow\Domain\Repository\StackRepository;
use JWeiland\IndexNow\Notifier\SearchEngineNotifier;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class NotifySearchEngineCommandTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'jweiland/indexnow',
    ];

    private SearchEngineNotifier&MockObject $searchEngineNotifierMock;

    private CommandTester $commandTester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->searchEngineNotifierMock = $this->createMock(SearchEngineNotifier::class);
        $this->commandTester = new CommandTester(new NotifySearchEngineCommand(
            $this->searchEngineNotifierMock,
            $this->get(StackRepository::class),
            new NullLogger(),
        ));
    }

    private function countStacks(): int
    {
        return $this->getConnectionPool()
            ->getConnectionForTable(StackRepository::TABLE)
            ->count('*', StackRepository::TABLE, []);
    }

    #[Test]
    public function executeWithEmptyStackSucceedsWithoutNotifying(): void
    {
        $this->searchEngineNotifierMock->expects(self::never())->method('notify');

        self::assertSame(Command::SUCCESS, $this->commandTester->execute([]));
        self::assertStringContainsString('No URL records available', $this->commandTester->getDisplay());
    }

    #[Test]
    public function executeNotifiesAllValidStacksAndDeletesThem(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/tx_indexnow_stack.csv');

        $this->searchEngineNotifierMock
            ->expects(self::once())
            ->method('notify')
            ->with(self::callback(static fn(array $stacks): bool => array_map(
                static fn(Stack $stack): string => $stack->getUrl(),
                $stacks,
            ) === ['https://example.com/', 'https://typo3.org/', 'https://jweiland.net/']))
            ->willReturn(true);

        self::assertSame(Command::SUCCESS, $this->commandTester->execute([]));
        self::assertStringContainsString('Batch sent (3 URLs), all entries deleted.', $this->commandTester->getDisplay());
        self::assertSame(0, $this->countStacks());
    }

    #[Test]
    public function executeDeletesStacksWithInvalidUrlWithoutNotifying(): void
    {
        $this->get(StackRepository::class)->insert('invalid-url', 1);

        $this->searchEngineNotifierMock->expects(self::never())->method('notify');

        self::assertSame(Command::SUCCESS, $this->commandTester->execute([]));
        self::assertStringContainsString('URL record with invalid URL found', $this->commandTester->getDisplay());
        self::assertSame(0, $this->countStacks());
    }

    #[Test]
    public function executeKeepsStacksIfNotificationFails(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/tx_indexnow_stack.csv');

        $this->searchEngineNotifierMock->expects(self::once())->method('notify')->willReturn(false);

        self::assertSame(Command::FAILURE, $this->commandTester->execute([]));
        self::assertStringContainsString('entries remain in stack', $this->commandTester->getDisplay());
        self::assertSame(3, $this->countStacks());
    }
}
