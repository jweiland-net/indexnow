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
use JWeiland\IndexNow\Hook\DataHandlerHook;
use JWeiland\IndexNow\Notifier\SearchEngineNotifier;
use JWeiland\IndexNow\Notifier\Submitter\BatchUrlSubmitter;
use JWeiland\IndexNow\Notifier\Submitter\SingleUrlSubmitter;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ServiceWiringTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'jweiland/indexnow',
    ];

    #[Test]
    public function dataHandlerHookCanBeInstantiatedByDataHandler(): void
    {
        self::assertInstanceOf(DataHandlerHook::class, GeneralUtility::makeInstance(DataHandlerHook::class));
    }

    #[Test]
    public function searchEngineNotifierReceivesAllTaggedUrlSubmitters(): void
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
        sort($urlSubmitterClasses);

        self::assertSame([BatchUrlSubmitter::class, SingleUrlSubmitter::class], $urlSubmitterClasses);
    }
}
