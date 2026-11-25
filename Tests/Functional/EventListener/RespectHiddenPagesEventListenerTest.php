<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\IndexNow\Tests\Functional\EventListener;

use JWeiland\IndexNow\Event\ModifyPageUidEvent;
use JWeiland\IndexNow\EventListener\RespectHiddenPagesEventListener;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class RespectHiddenPagesEventListenerTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'jweiland/indexnow',
    ];

    /**
     * @return array<string, array{array<string, mixed>|null, int}>
     */
    public static function pageRecordDataProvider(): array
    {
        return [
            'no page record' => [null, 1],
            'page record without hidden field' => [['uid' => 1, 'title' => 'Home'], 1],
            'visible page' => [['uid' => 1, 'hidden' => 0], 1],
            'visible page from database (string)' => [['uid' => 1, 'hidden' => '0'], 1],
            'hidden page' => [['uid' => 1, 'hidden' => 1], 0],
            'hidden page from database (string)' => [['uid' => 1, 'hidden' => '1'], 0],
        ];
    }

    /**
     * @param array<string, mixed>|null $pageRecord
     */
    #[Test]
    #[DataProvider('pageRecordDataProvider')]
    public function invokeSkipsHiddenPages(?array $pageRecord, int $expectedPageUid): void
    {
        $event = new ModifyPageUidEvent(['uid' => 123, 'pid' => 1], 'tt_content', 1, $pageRecord);

        (new RespectHiddenPagesEventListener($this->get(TcaSchemaFactory::class)))($event);

        self::assertSame($expectedPageUid, $event->getPageUid());
    }
}
