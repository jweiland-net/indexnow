<?php

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\IndexNow\Tests\Functional\Event;

use JWeiland\IndexNow\Event\ProvidePreviewUrlEvent;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case
 */
class ProvidePreviewUrlEventTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'jweiland/indexnow',
    ];

    #[Test]
    public function getTableWillReturnTable(): void
    {
        $subject = new ProvidePreviewUrlEvent(
            table: 'tx_news_domain_model_news',
            recordUid: 123,
            record: [
                'uid' => 123,
            ],
        );

        self::assertSame(
            'tx_news_domain_model_news',
            $subject->getTable(),
        );
    }

    #[Test]
    public function getRecordUidWithIntegerWillReturnRecordUid(): void
    {
        $subject = new ProvidePreviewUrlEvent(
            table: 'tx_news_domain_model_news',
            recordUid: 123,
            record: [
                'uid' => 123,
            ],
        );

        self::assertSame(
            123,
            $subject->getRecordUid(),
        );
    }

    #[Test]
    public function getRecordUidWithStringWillReturnRecordUid(): void
    {
        $subject = new ProvidePreviewUrlEvent(
            table: 'tx_news_domain_model_news',
            recordUid: 'NEW15243',
            record: [
                'uid' => 123,
            ],
        );

        self::assertSame(
            'NEW15243',
            $subject->getRecordUid(),
        );
    }

    #[Test]
    public function getRecordWillReturnRecord(): void
    {
        $subject = new ProvidePreviewUrlEvent(
            table: 'tx_news_domain_model_news',
            recordUid: 123,
            record: [
                'uid' => 123,
            ],
        );

        self::assertSame(
            [
                'uid' => 123,
            ],
            $subject->getRecord(),
        );
    }

    #[Test]
    public function getPreviewUrlsWillInitiallyReturnEmptyArray(): void
    {
        $subject = new ProvidePreviewUrlEvent(
            table: 'tx_news_domain_model_news',
            recordUid: 123,
            record: [
                'uid' => 123,
            ],
        );

        self::assertSame(
            [],
            $subject->getPreviewUrls(),
        );
    }

    #[Test]
    public function addPreviewUrlWillAddPreviewUrl(): void
    {
        $subject = new ProvidePreviewUrlEvent(
            table: 'tx_news_domain_model_news',
            recordUid: 123,
            record: [
                'uid' => 123,
            ],
        );

        $subject->addPreviewUrl('https://example.com/preview/123');

        $previewUrls = $subject->getPreviewUrls();

        self::assertCount(
            1,
            $previewUrls,
        );
        self::assertSame(
            'https://example.com/preview/123',
            $previewUrls[0]->getUrl(),
        );
        self::assertNull(
            $previewUrls[0]->getPageUid(),
        );
    }

    #[Test]
    public function addPreviewUrlWithPageUidWillAddPreviewUrlWithPageUid(): void
    {
        $subject = new ProvidePreviewUrlEvent(
            table: 'tx_news_domain_model_news',
            recordUid: 123,
            record: [
                'uid' => 123,
            ],
        );

        $subject->addPreviewUrl('https://other-site.com/preview/123', 16);

        self::assertSame(
            16,
            $subject->getPreviewUrls()[0]->getPageUid(),
        );
    }

    #[Test]
    public function addPreviewUrlCanBeCalledMultipleTimes(): void
    {
        $subject = new ProvidePreviewUrlEvent(
            table: 'tx_news_domain_model_news',
            recordUid: 123,
            record: [
                'uid' => 123,
            ],
        );

        $subject->addPreviewUrl('https://example.com/preview/123');
        $subject->addPreviewUrl('https://other-site.com/preview/123', 16);

        self::assertCount(
            2,
            $subject->getPreviewUrls(),
        );
    }

    #[Test]
    public function addPreviewUrlWithInvalidUrlWillNotAddPreviewUrl(): void
    {
        $subject = new ProvidePreviewUrlEvent(
            table: 'tx_news_domain_model_news',
            recordUid: 123,
            record: [
                'uid' => 123,
            ],
        );

        $subject->addPreviewUrl('invalid-url');

        self::assertSame(
            [],
            $subject->getPreviewUrls(),
        );
    }

    #[Test]
    public function getPreviewUrlWillInitiallyReturnEmptyString(): void
    {
        $subject = new ProvidePreviewUrlEvent(
            table: 'tx_news_domain_model_news',
            recordUid: 123,
            record: [
                'uid' => 123,
            ],
        );

        self::assertSame(
            '',
            $subject->getPreviewUrl(),
        );
    }

    #[Test]
    public function setPreviewUrlWillSetPreviewUrl(): void
    {
        $subject = new ProvidePreviewUrlEvent(
            table: 'tx_news_domain_model_news',
            recordUid: 123,
            record: [
                'uid' => 123,
            ],
        );

        $subject->setPreviewUrl('https://example.com/preview/123');

        self::assertSame(
            'https://example.com/preview/123',
            $subject->getPreviewUrl(),
        );
    }

    #[Test]
    public function setPreviewUrlWithInvalidUrlWillNotSetPreviewUrl(): void
    {
        $subject = new ProvidePreviewUrlEvent(
            table: 'tx_news_domain_model_news',
            recordUid: 123,
            record: [
                'uid' => 123,
            ],
        );

        $subject->setPreviewUrl('invalid-url');

        self::assertSame(
            '',
            $subject->getPreviewUrl(),
        );
    }

    #[Test]
    public function setPreviewUrlIsReflectedInGetPreviewUrls(): void
    {
        $subject = new ProvidePreviewUrlEvent(
            table: 'tx_news_domain_model_news',
            recordUid: 123,
            record: [
                'uid' => 123,
            ],
        );

        $subject->setPreviewUrl('https://example.com/preview/123');

        $previewUrls = $subject->getPreviewUrls();

        self::assertCount(
            1,
            $previewUrls,
        );
        self::assertSame(
            'https://example.com/preview/123',
            $previewUrls[0]->getUrl(),
        );
    }
}
