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
use JWeiland\IndexNow\Domain\Model\Stack;
use JWeiland\IndexNow\Notifier\Submitter\BatchUrlSubmitter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Stub;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteSettings;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class BatchUrlSubmitterTest extends UnitTestCase
{
    private SiteFinder&Stub $siteFinderStub;

    protected function setUp(): void
    {
        parent::setUp();

        $this->siteFinderStub = self::createStub(SiteFinder::class);
    }

    private function createSubject(RequestFactory $requestFactory, ?LoggerInterface $logger = null): BatchUrlSubmitter
    {
        return new BatchUrlSubmitter(
            $requestFactory,
            new ApiKeyResolver($this->siteFinderStub),
            new ExtConf(notifyBatchMode: true),
            $logger ?? self::createStub(LoggerInterface::class),
        );
    }

    private function useApiKey(string $apiKey): void
    {
        $this->siteFinderStub->method('getSiteByPageId')->willReturn(new Site(
            'example',
            1,
            ['base' => 'https://example.com/'],
            SiteSettings::createFromSettingsTree(['indexnow' => ['apiKey' => $apiKey]]),
        ));
    }

    /**
     * @return non-empty-list<Stack>
     */
    private function createStacks(int $amount): array
    {
        $stacks = [new Stack(1, 'https://example.com/page-1', 10)];
        for ($i = 2; $i <= $amount; $i++) {
            $stacks[] = new Stack($i, 'https://example.com/page-' . $i, 10);
        }

        return $stacks;
    }

    #[Test]
    public function submitSendsJsonPostRequest(): void
    {
        $requestFactory = $this->createMock(RequestFactory::class);
        $this->useApiKey('SuperSafeApiKey');
        $requestFactory
            ->expects(self::once())
            ->method('request')
            ->with('https://www.bing.com/indexnow', 'POST', [
                'headers' => ['Content-Type' => 'application/json'],
                'body' => json_encode([
                    'host' => 'example.com',
                    'key' => 'SuperSafeApiKey',
                    'keyLocation' => 'https://example.com/SuperSafeApiKey.txt',
                    'urlList' => ['https://example.com/page-1', 'https://example.com/page-2'],
                ]),
            ])
            ->willReturn(new Response('php://temp', 202));

        self::assertTrue($this->createSubject($requestFactory)->submit('example.com', $this->createStacks(2)));
    }

    #[Test]
    public function submitSplitsUrlListIntoChunksOf10000Urls(): void
    {
        $requestFactory = $this->createMock(RequestFactory::class);
        $this->useApiKey('SuperSafeApiKey');
        $requestFactory
            ->expects(self::exactly(2))
            ->method('request')
            ->willReturn(new Response());

        self::assertTrue($this->createSubject($requestFactory)->submit('example.com', $this->createStacks(10001)));
    }

    #[Test]
    public function submitReturnsFalseAndLogsWarningOnUnexpectedStatusCode(): void
    {
        $requestFactory = self::createStub(RequestFactory::class);
        $logger = $this->createMock(LoggerInterface::class);
        $this->useApiKey('SuperSafeApiKey');
        $requestFactory->method('request')->willReturn(new Response('php://temp', 403));
        $logger->expects(self::once())->method('warning');

        self::assertFalse($this->createSubject($requestFactory, $logger)->submit('example.com', $this->createStacks(2)));
    }

    #[Test]
    public function submitReturnsFalseAndLogsErrorOnRequestException(): void
    {
        $requestFactory = self::createStub(RequestFactory::class);
        $logger = $this->createMock(LoggerInterface::class);
        $this->useApiKey('SuperSafeApiKey');
        $requestFactory->method('request')->willThrowException(new \RuntimeException('Connection refused'));
        $logger
            ->expects(self::once())
            ->method('error')
            ->with(self::anything(), ['host' => 'example.com', 'message' => 'Connection refused']);

        self::assertFalse($this->createSubject($requestFactory, $logger)->submit('example.com', $this->createStacks(2)));
    }

    #[Test]
    public function submitReturnsFalseAndLogsErrorIfApiKeyIsMissing(): void
    {
        $requestFactory = $this->createMock(RequestFactory::class);
        $logger = $this->createMock(LoggerInterface::class);
        $this->siteFinderStub->method('getSiteByPageId')->willThrowException(new SiteNotFoundException('', 1));
        $requestFactory->expects(self::never())->method('request');
        $logger->expects(self::once())->method('error');

        self::assertFalse($this->createSubject($requestFactory, $logger)->submit('example.com', $this->createStacks(2)));
    }
}
