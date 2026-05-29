<?php

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\IndexNow\Tests\Functional\Middleware;

use JWeiland\IndexNow\Configuration\Exception\ApiKeyNotAvailableException;
use JWeiland\IndexNow\Configuration\ExtConf;
use JWeiland\IndexNow\Middleware\ApiKeyVerificationMiddleware;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case
 */
class ApiKeyVerificationMiddlewareTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'jweiland/indexnow',
    ];

    private ApiKeyVerificationMiddleware $subject;

    private ExtConf|MockObject $extConfMock;

    private RequestHandlerInterface|MockObject $handlerMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extConfMock = $this->createMock(ExtConf::class);
        $this->handlerMock = $this->createMock(RequestHandlerInterface::class);
        $this->subject = new ApiKeyVerificationMiddleware($this->extConfMock);
    }

    protected function tearDown(): void
    {
        unset(
            $this->subject,
            $this->extConfMock,
            $this->handlerMock,
        );

        parent::tearDown();
    }

    #[Test]
    public function processPassesThroughNonTxtRequest(): void
    {
        $handlerResponse = $this->createMock(ResponseInterface::class);
        $this->handlerMock->expects(self::once())->method('handle')->willReturn($handlerResponse);
        $this->extConfMock->expects(self::never())->method('getApiKey');

        $request = new ServerRequest('https://example.com/some-page', 'GET');
        $response = $this->subject->process($request, $this->handlerMock);

        self::assertSame($handlerResponse, $response);
    }

    #[Test]
    public function processPassesThroughSubdirectoryTxtRequest(): void
    {
        $handlerResponse = $this->createMock(ResponseInterface::class);
        $this->handlerMock->expects(self::once())->method('handle')->willReturn($handlerResponse);
        $this->extConfMock->expects(self::never())->method('getApiKey');

        $request = new ServerRequest('https://example.com/subdir/key.txt', 'GET');
        $response = $this->subject->process($request, $this->handlerMock);

        self::assertSame($handlerResponse, $response);
    }

    #[Test]
    public function processPassesThroughWhenNoApiKeyConfigured(): void
    {
        $handlerResponse = $this->createMock(ResponseInterface::class);
        $this->handlerMock->expects(self::once())->method('handle')->willReturn($handlerResponse);
        $this->extConfMock->expects(self::once())->method('getApiKey')
            ->willThrowException(new ApiKeyNotAvailableException('No API key configured', 1636752398));

        $request = new ServerRequest('https://example.com/some-key.txt', 'GET');
        $response = $this->subject->process($request, $this->handlerMock);

        self::assertSame($handlerResponse, $response);
    }

    #[Test]
    public function processPassesThroughForNonMatchingTxtFile(): void
    {
        $handlerResponse = $this->createMock(ResponseInterface::class);
        $this->handlerMock->expects(self::once())->method('handle')->willReturn($handlerResponse);
        $this->extConfMock->expects(self::once())->method('getApiKey')->willReturn('my-api-key');

        $request = new ServerRequest('https://example.com/other-file.txt', 'GET');
        $response = $this->subject->process($request, $this->handlerMock);

        self::assertSame($handlerResponse, $response);
    }

    #[Test]
    public function processReturnsApiKeyFileForMatchingRequest(): void
    {
        $this->handlerMock->expects(self::never())->method('handle');
        $this->extConfMock->expects(self::once())->method('getApiKey')->willReturn('my-api-key');

        $request = new ServerRequest('https://example.com/my-api-key.txt', 'GET');
        $response = $this->subject->process($request, $this->handlerMock);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('text/plain; charset=utf-8', $response->getHeaderLine('Content-Type'));
        self::assertSame('public, max-age=86400', $response->getHeaderLine('Cache-Control'));
        self::assertSame('10', $response->getHeaderLine('Content-Length'));
        self::assertSame('my-api-key', (string)$response->getBody());
    }
}