<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\IndexNow\Notifier\Submitter;

use JWeiland\IndexNow\Domain\Model\Stack;

/**
 * Strategy to submit the URLs of one host to the IndexNow endpoint.
 *
 * Implementations are collected via the DI tag "indexnow.url_submitter".
 */
interface UrlSubmitterInterface
{
    public const TAG = 'indexnow.url_submitter';

    public function supports(int $numberOfStacks): bool;

    /**
     * @param non-empty-list<Stack> $stacks All stacks belong to the given host
     */
    public function submit(string $host, array $stacks): bool;
}
