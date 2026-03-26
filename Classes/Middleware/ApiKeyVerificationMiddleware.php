<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\IndexNow\Middleware;

use JWeiland\IndexNow\Configuration\ExtConf;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\Stream;

/**
 * Serves the IndexNow API key verification file at /{apiKey}.txt
 *
 * IndexNow requires a verification file at the root of the website
 * whose filename and content match the configured API key. This
 * middleware serves that file dynamically from the extension configuration,
 * removing the need to manually create or update a static file.
 *
 * @see https://www.indexnow.org/documentation
 */
class ApiKeyVerificationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly ExtConf $extConf,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = ltrim($request->getUri()->getPath(), '/');

        // Quick exit: only handle .txt files at root level (no slashes in path)
        if (!str_ends_with($path, '.txt') || str_contains($path, '/')) {
            return $handler->handle($request);
        }

        try {
            $apiKey = $this->extConf->getApiKey();
        } catch (\Exception) {
            // No API key configured — skip
            return $handler->handle($request);
        }

        if ($path !== $apiKey . '.txt') {
            return $handler->handle($request);
        }

        $stream = new Stream('php://temp', 'rw');
        $stream->write($apiKey);
        $stream->rewind();

        return new Response($stream, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
