<?php

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\IndexNow\Tests\Unit\Middleware;

use JWeiland\IndexNow\Middleware\ApiKeyVerificationMiddleware;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteSettings;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ApiKeyVerificationMiddlewareTest extends UnitTestCase
{
    private ApiKeyVerificationMiddleware $subject;

    private RequestHandlerInterface&MockObject $handlerMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->handlerMock = $this->createMock(RequestHandlerInterface::class);
        $this->subject = new ApiKeyVerificationMiddleware();
    }

    protected function tearDown(): void
    {
        unset(
            $this->subject,
            $this->handlerMock,
        );

        parent::tearDown();
    }

    private function makeSite(string $base, string $apiKey): Site
    {
        return new Site(
            'test',
            1,
            ['base' => $base],
            SiteSettings::createFromSettingsTree(['indexnow' => ['apiKey' => $apiKey]]),
        );
    }

    #[Test]
    public function processPassesThroughWhenNoSiteAttributePresent(): void
    {
        $handlerResponse = $this->createMock(ResponseInterface::class);
        $this->handlerMock->expects(self::once())->method('handle')->willReturn($handlerResponse);

        $request = new ServerRequest('https://example.com/some-key.txt', 'GET', 'php://input', [], ['REQUEST_URI' => '/some-key.txt']);
        $response = $this->subject->process($request, $this->handlerMock);

        self::assertSame($handlerResponse, $response);
    }

    #[Test]
    public function processPassesThroughWhenNoApiKeyConfigured(): void
    {
        $handlerResponse = $this->createMock(ResponseInterface::class);
        $this->handlerMock->expects(self::once())->method('handle')->willReturn($handlerResponse);

        $site = new Site('test', 1, ['base' => 'https://example.com/'], SiteSettings::createFromSettingsTree([]));
        $request = (new ServerRequest('https://example.com/some-key.txt', 'GET', 'php://input', [], ['REQUEST_URI' => '/some-key.txt']))
            ->withAttribute('site', $site);
        $response = $this->subject->process($request, $this->handlerMock);

        self::assertSame($handlerResponse, $response);
    }

    #[Test]
    public function processPassesThroughNonTxtRequest(): void
    {
        $handlerResponse = $this->createMock(ResponseInterface::class);
        $this->handlerMock->expects(self::once())->method('handle')->willReturn($handlerResponse);

        $site = $this->makeSite('https://example.com/', 'my-api-key');
        $request = (new ServerRequest('https://example.com/some-page', 'GET', 'php://input', [], ['REQUEST_URI' => '/some-page']))
            ->withAttribute('site', $site);
        $response = $this->subject->process($request, $this->handlerMock);

        self::assertSame($handlerResponse, $response);
    }

    #[Test]
    public function processPassesThroughSubdirectoryTxtRequest(): void
    {
        $handlerResponse = $this->createMock(ResponseInterface::class);
        $this->handlerMock->expects(self::once())->method('handle')->willReturn($handlerResponse);

        $site = $this->makeSite('https://example.com/', 'my-api-key');
        $request = (new ServerRequest('https://example.com/subdir/my-api-key.txt', 'GET', 'php://input', [], ['REQUEST_URI' => '/subdir/my-api-key.txt']))
            ->withAttribute('site', $site);
        $response = $this->subject->process($request, $this->handlerMock);

        self::assertSame($handlerResponse, $response);
    }

    #[Test]
    public function processPassesThroughForNonMatchingTxtFile(): void
    {
        $handlerResponse = $this->createMock(ResponseInterface::class);
        $this->handlerMock->expects(self::once())->method('handle')->willReturn($handlerResponse);

        $site = $this->makeSite('https://example.com/', 'my-api-key');
        $request = (new ServerRequest('https://example.com/other-file.txt', 'GET', 'php://input', [], ['REQUEST_URI' => '/other-file.txt']))
            ->withAttribute('site', $site);
        $response = $this->subject->process($request, $this->handlerMock);

        self::assertSame($handlerResponse, $response);
    }

    #[Test]
    public function processReturnsApiKeyFileForMatchingRequest(): void
    {
        $this->handlerMock->expects(self::never())->method('handle');

        $site = $this->makeSite('https://example.com/', 'my-api-key');
        $request = (new ServerRequest('https://example.com/my-api-key.txt', 'GET', 'php://input', [], ['REQUEST_URI' => '/my-api-key.txt']))
            ->withAttribute('site', $site);
        $response = $this->subject->process($request, $this->handlerMock);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('text/plain; charset=utf-8', $response->getHeaderLine('Content-Type'));
        self::assertSame('public, max-age=86400', $response->getHeaderLine('Cache-Control'));
        self::assertSame('10', $response->getHeaderLine('Content-Length'));
        self::assertSame('my-api-key', (string)$response->getBody());
    }

    #[Test]
    public function processHandlesSiteWithSubdirectoryBase(): void
    {
        $this->handlerMock->expects(self::never())->method('handle');

        $site = $this->makeSite('https://example.com/de/', 'my-api-key');
        $request = (new ServerRequest('https://example.com/de/my-api-key.txt', 'GET', 'php://input', [], ['REQUEST_URI' => '/de/my-api-key.txt']))
            ->withAttribute('site', $site);
        $response = $this->subject->process($request, $this->handlerMock);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('my-api-key', (string)$response->getBody());
    }
}
