<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\IndexNow\Tests\Unit\Notifier\Submitter;

use GuzzleHttp\Exception\ClientException;
use JWeiland\IndexNow\Configuration\ApiKeyResolver;
use JWeiland\IndexNow\Configuration\Exception\ApiKeyNotAvailableException;
use JWeiland\IndexNow\Configuration\ExtConf;
use JWeiland\IndexNow\Domain\Model\Stack;
use JWeiland\IndexNow\Notifier\Submitter\SingleUrlSubmitter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Stub;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Http\Request;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteSettings;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class SingleUrlSubmitterTest extends UnitTestCase
{
    private SiteFinder&Stub $siteFinderStub;

    protected function setUp(): void
    {
        parent::setUp();

        $this->siteFinderStub = self::createStub(SiteFinder::class);
    }

    private function createSubject(RequestFactory $requestFactory, ?LoggerInterface $logger = null): SingleUrlSubmitter
    {
        return new SingleUrlSubmitter(
            $requestFactory,
            new ApiKeyResolver($this->siteFinderStub),
            new ExtConf(),
            $logger ?? self::createStub(LoggerInterface::class),
        );
    }

    private function siteWithApiKey(string $apiKey): Site
    {
        return new Site(
            'example',
            1,
            ['base' => 'https://example.com/'],
            SiteSettings::createFromSettingsTree(['indexnow' => ['apiKey' => $apiKey]]),
        );
    }

    #[Test]
    public function submitSendsGetRequestWithUrlAndApiKey(): void
    {
        $requestFactory = $this->createMock(RequestFactory::class);
        $this->siteFinderStub->method('getSiteByPageId')->willReturn($this->siteWithApiKey('SuperSafeApiKey'));
        $requestFactory
            ->expects(self::once())
            ->method('request')
            ->with('https://www.bing.com/indexnow?url=https%3A%2F%2Fexample.com%2Fevents2&key=SuperSafeApiKey')
            ->willReturn(new Response());

        $subject = $this->createSubject($requestFactory);

        self::assertTrue($subject->submit('example.com', [new Stack(1, 'https://example.com/events2', 10)]));
    }

    #[Test]
    public function submitTreatsStatusCode202AsSuccessAndLogsPendingValidation(): void
    {
        $requestFactory = self::createStub(RequestFactory::class);
        $logger = $this->createMock(LoggerInterface::class);
        $this->siteFinderStub->method('getSiteByPageId')->willReturn($this->siteWithApiKey('SuperSafeApiKey'));
        $requestFactory->method('request')->willReturn(new Response('php://temp', 202));
        $logger
            ->expects(self::once())
            ->method('info')
            ->with('IndexNow received URL, but IndexNow key validation is still pending');

        $subject = $this->createSubject($requestFactory, $logger);

        self::assertTrue($subject->submit('example.com', [new Stack(1, 'https://example.com/', 10)]));
    }

    #[Test]
    public function submitReturnsFalseAndLogsWarningOnUnexpectedStatusCode(): void
    {
        $requestFactory = self::createStub(RequestFactory::class);
        $logger = $this->createMock(LoggerInterface::class);
        $this->siteFinderStub->method('getSiteByPageId')->willReturn($this->siteWithApiKey('SuperSafeApiKey'));
        $requestFactory->method('request')->willReturn(new Response('php://temp', 422));
        $logger
            ->expects(self::once())
            ->method('warning')
            ->with(
                self::stringContains('status code {statusCode}'),
                self::callback(static fn(array $context): bool => $context['statusCode'] === 422),
            );

        $subject = $this->createSubject($requestFactory, $logger);

        self::assertFalse($subject->submit('example.com', [new Stack(1, 'https://example.com/', 10)]));
    }

    #[Test]
    public function submitReturnsFalseAndLogsErrorOnClientException(): void
    {
        $requestFactory = self::createStub(RequestFactory::class);
        $logger = $this->createMock(LoggerInterface::class);
        $this->siteFinderStub->method('getSiteByPageId')->willReturn($this->siteWithApiKey('SuperSafeApiKey'));
        $requestFactory->method('request')->willThrowException(
            new ClientException('Forbidden', new Request('https://www.bing.com/indexnow'), new Response('php://temp', 403)),
        );
        $logger
            ->expects(self::once())
            ->method('error')
            ->with(self::anything(), ['message' => 'Forbidden']);

        $subject = $this->createSubject($requestFactory, $logger);

        self::assertFalse($subject->submit('example.com', [new Stack(1, 'https://example.com/', 10)]));
    }

    #[Test]
    public function submitSkipsStacksWithInvalidUrl(): void
    {
        $requestFactory = $this->createMock(RequestFactory::class);
        $requestFactory->expects(self::never())->method('request');

        $subject = $this->createSubject($requestFactory);

        self::assertFalse($subject->submit('example.com', [new Stack(1, 'invalid', 10)]));
    }

    #[Test]
    public function submitContinuesWithRemainingStacksAfterFailure(): void
    {
        $requestFactory = $this->createMock(RequestFactory::class);
        $this->siteFinderStub->method('getSiteByPageId')->willReturn($this->siteWithApiKey('SuperSafeApiKey'));
        $requestFactory
            ->expects(self::exactly(2))
            ->method('request')
            ->willReturnOnConsecutiveCalls(new Response('php://temp', 500), new Response());

        self::assertFalse($this->createSubject($requestFactory)->submit('example.com', [
            new Stack(1, 'https://example.com/a', 10),
            new Stack(2, 'https://example.com/b', 10),
        ]));
    }

    #[Test]
    public function submitThrowsExceptionIfApiKeyIsMissing(): void
    {
        $requestFactory = $this->createMock(RequestFactory::class);
        $this->siteFinderStub->method('getSiteByPageId')->willReturn($this->siteWithApiKey(''));
        $requestFactory->expects(self::never())->method('request');

        $this->expectException(ApiKeyNotAvailableException::class);

        $this->createSubject($requestFactory)->submit('example.com', [new Stack(1, 'https://example.com/', 10)]);
    }
}
