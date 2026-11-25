<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\IndexNow\Notifier;

use JWeiland\IndexNow\Configuration\Exception\ApiKeyNotAvailableException;
use JWeiland\IndexNow\Domain\Model\Stack;
use JWeiland\IndexNow\Notifier\Submitter\UrlSubmitterInterface;
use Psr\Log\LoggerInterface;

/**
 * Groups queued URLs by host and delegates each group to the matching submit strategy.
 *
 * The URL submitters are tagged with "indexnow.url_submitter" and ordered by the tag priority.
 * The first submitter supporting the number of URLs of a host wins. To replace a built-in
 * submitter, register your own UrlSubmitterInterface implementation with a higher priority.
 *
 * Designed for extension (XCLASS or Symfony service alias): methods and properties are protected.
 */
class SearchEngineNotifier
{
    /**
     * @param iterable<UrlSubmitterInterface> $urlSubmitters
     */
    public function __construct(
        protected readonly iterable $urlSubmitters,
        protected readonly LoggerInterface $logger,
    ) {}

    /**
     * @param list<Stack> $stacks
     * @return bool FALSE, if there was nothing to send
     * @throws ApiKeyNotAvailableException
     */
    public function notify(array $stacks): bool
    {
        if ($stacks === []) {
            $this->logger->info('No URL records available, nothing sent.');

            return false;
        }

        foreach ($this->groupStacksByHost($stacks) as $host => $stacksOfHost) {
            $numberOfStacks = count($stacksOfHost);
            $this->logger->info(sprintf('Preparing batch for %s with %d URL(s)', $host, $numberOfStacks));

            $this->getUrlSubmitter($numberOfStacks)?->submit($host, $stacksOfHost);
        }

        return true;
    }

    protected function getUrlSubmitter(int $numberOfStacks): ?UrlSubmitterInterface
    {
        foreach ($this->urlSubmitters as $urlSubmitter) {
            if ($urlSubmitter->supports($numberOfStacks)) {
                return $urlSubmitter;
            }
        }

        $this->logger->error(sprintf('No IndexNow URL submitter found for %d URL(s)', $numberOfStacks));

        return null;
    }

    /**
     * @param list<Stack> $stacks
     * @return array<string, non-empty-list<Stack>>
     */
    protected function groupStacksByHost(array $stacks): array
    {
        $groupedStacks = [];

        foreach ($stacks as $stack) {
            $host = $stack->getHost();
            if ($host === '') {
                $this->logger->warning('Skipping URL with invalid host: ' . $stack->getUrl());
                continue;
            }

            $groupedStacks[$host][] = $stack;
        }

        return $groupedStacks;
    }
}
