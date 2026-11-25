<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\IndexNow\Tests\Unit\Notifier;

use JWeiland\IndexNow\Domain\Model\Stack;
use JWeiland\IndexNow\Notifier\SearchEngineNotifier;
use JWeiland\IndexNow\Notifier\Submitter\UrlSubmitterInterface;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class SearchEngineNotifierTest extends UnitTestCase
{
    #[Test]
    public function notifyGroupsStacksByHostAndDelegatesToSupportingSubmitter(): void
    {
        $exampleStacks = [new Stack(1, 'https://example.com/a', 10), new Stack(2, 'https://example.com/b', 10)];
        $typo3Stacks = [new Stack(3, 'https://typo3.org/', 20)];
        $submittedHosts = [];

        $urlSubmitterMock = $this->createMock(UrlSubmitterInterface::class);
        $urlSubmitterMock->method('supports')->willReturn(true);
        $urlSubmitterMock
            ->expects(self::exactly(2))
            ->method('submit')
            ->willReturnCallback(static function (string $host, array $stacks) use (&$submittedHosts): bool {
                $submittedHosts[$host] = $stacks;

                return true;
            });

        $subject = new SearchEngineNotifier([$urlSubmitterMock], self::createStub(LoggerInterface::class));

        self::assertTrue($subject->notify([...$exampleStacks, ...$typo3Stacks]));
        self::assertSame(['example.com' => $exampleStacks, 'typo3.org' => $typo3Stacks], $submittedHosts);
    }

    #[Test]
    public function notifySkipsStacksWithoutHost(): void
    {
        $urlSubmitterMock = $this->createMock(UrlSubmitterInterface::class);
        $urlSubmitterMock->expects(self::never())->method('submit');

        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects(self::once())->method('warning')->with('Skipping URL with invalid host: invalid');

        $subject = new SearchEngineNotifier([$urlSubmitterMock], $loggerMock);

        self::assertTrue($subject->notify([new Stack(1, 'invalid', 10)]));
    }

    #[Test]
    public function notifyLogsErrorIfNoSubmitterSupportsTheStacks(): void
    {
        $urlSubmitterMock = $this->createMock(UrlSubmitterInterface::class);
        $urlSubmitterMock->method('supports')->willReturn(false);
        $urlSubmitterMock->expects(self::never())->method('submit');

        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects(self::once())->method('error')->with('No IndexNow URL submitter found for 1 URL(s)');

        $subject = new SearchEngineNotifier([$urlSubmitterMock], $loggerMock);

        self::assertTrue($subject->notify([new Stack(1, 'https://example.com/', 10)]));
    }
}
