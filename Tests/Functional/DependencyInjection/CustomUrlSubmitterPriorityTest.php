<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\IndexNow\Tests\Functional\DependencyInjection;

use JWeiland\IndexNow\Command\NotifySearchEngineCommand;
use JWeiland\IndexNow\Notifier\SearchEngineNotifier;
use JWeiland\IndexNow\Notifier\Submitter\BatchUrlSubmitter;
use JWeiland\IndexNow\Notifier\Submitter\SingleUrlSubmitter;
use JWeiland\IndexNow\Tests\Functional\Fixtures\Extensions\indexnow_custom_submitter\Classes\CustomUrlSubmitter;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Third-party extensions can replace the built-in URL submitters by tagging
 * their own UrlSubmitterInterface implementation with a higher priority.
 */
final class CustomUrlSubmitterPriorityTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'jweiland/indexnow',
        __DIR__ . '/../Fixtures/Extensions/indexnow_custom_submitter',
    ];

    #[Test]
    public function urlSubmittersAreOrderedByTagPriority(): void
    {
        $command = $this->get(NotifySearchEngineCommand::class);
        $notifier = (new \ReflectionProperty($command, 'searchEngineNotifier'))->getValue($command);
        self::assertInstanceOf(SearchEngineNotifier::class, $notifier);

        $urlSubmitters = (new \ReflectionProperty($notifier, 'urlSubmitters'))->getValue($notifier);
        self::assertIsIterable($urlSubmitters);

        $urlSubmitterClasses = [];
        foreach ($urlSubmitters as $urlSubmitter) {
            $urlSubmitterClasses[] = $urlSubmitter::class;
        }

        self::assertSame(CustomUrlSubmitter::class, $urlSubmitterClasses[0] ?? null);
        self::assertEqualsCanonicalizing(
            [CustomUrlSubmitter::class, SingleUrlSubmitter::class, BatchUrlSubmitter::class],
            $urlSubmitterClasses,
        );
    }
}
