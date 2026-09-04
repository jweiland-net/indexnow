<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\IndexNow\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Site\Entity\Site;

/**
 * Serves the IndexNow API key verification file at /{apiKey}.txt
 *
 * IndexNow requires a verification file at the root of the website
 * whose filename and content match the configured API key. This
 * middleware dynamically serves that endpoint, using the API key
 * stored in the extension configuration,
 * removing the need to manually create or update a static file.
 *
 * @see https://www.indexnow.org/documentation
 */
final readonly class ApiKeyVerificationMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $site = $request->getAttribute('site');
        if (!$site instanceof Site) {
            return $handler->handle($request);
        }

        $apiKey = (string)$site->getSettings()->get('indexnow.apiKey', '');
        if ($apiKey === '') {
            return $handler->handle($request);
        }

        $serverParams = $request->getServerParams();
        $requestPath = parse_url($serverParams['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        if (!is_string($requestPath)) {
            return $handler->handle($request);
        }

        $path = ltrim($requestPath, '/');
        $sitePath = ltrim($site->getBase()->getPath(), '/');
        if ($sitePath !== '' && str_starts_with($path, $sitePath)) {
            $path = ltrim(substr($path, strlen($sitePath)), '/');
        }

        if ($path !== $apiKey . '.txt') {
            return $handler->handle($request);
        }

        return new HtmlResponse(
            $apiKey,
            200,
            [
                'Content-Type' => 'text/plain; charset=utf-8',
                'Content-Length' => (string)strlen($apiKey),
                'Cache-Control' => 'public, max-age=86400',
            ],
        );
    }
}
