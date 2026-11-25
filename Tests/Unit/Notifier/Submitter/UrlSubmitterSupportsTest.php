<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\IndexNow\Tests\Unit\Notifier\Submitter;

use JWeiland\IndexNow\Configuration\ApiKeyResolver;
use JWeiland\IndexNow\Configuration\ExtConf;
use JWeiland\IndexNow\Notifier\Submitter\BatchUrlSubmitter;
use JWeiland\IndexNow\Notifier\Submitter\SingleUrlSubmitter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\NullLogger;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class UrlSubmitterSupportsTest extends UnitTestCase
{
    /**
     * @return array<string, array{bool, int, bool, bool}>
     */
    public static function supportsDataProvider(): array
    {
        return [
            'single mode, one URL' => [false, 1, true, false],
            'single mode, many URLs' => [false, 5, true, false],
            'batch mode, one URL' => [true, 1, true, false],
            'batch mode, two URLs' => [true, 2, false, true],
            'batch mode, many URLs' => [true, 5, false, true],
        ];
    }

    #[Test]
    #[DataProvider('supportsDataProvider')]
    public function exactlyOneSubmitterSupportsTheGivenNumberOfUrls(
        bool $notifyBatchMode,
        int $numberOfStacks,
        bool $expectedSingle,
        bool $expectedBatch,
    ): void {
        $extConf = new ExtConf(notifyBatchMode: $notifyBatchMode);
        $requestFactory = self::createStub(RequestFactory::class);
        $apiKeyResolver = new ApiKeyResolver(self::createStub(SiteFinder::class));

        $singleUrlSubmitter = new SingleUrlSubmitter($requestFactory, $apiKeyResolver, $extConf, new NullLogger());
        $batchUrlSubmitter = new BatchUrlSubmitter($requestFactory, $apiKeyResolver, $extConf, new NullLogger());

        self::assertSame($expectedSingle, $singleUrlSubmitter->supports($numberOfStacks));
        self::assertSame($expectedBatch, $batchUrlSubmitter->supports($numberOfStacks));
    }
}
