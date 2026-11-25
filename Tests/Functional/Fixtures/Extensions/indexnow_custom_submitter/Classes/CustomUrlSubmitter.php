<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\IndexNow\Tests\Functional\Fixtures\Extensions\indexnow_custom_submitter\Classes;

use JWeiland\IndexNow\Notifier\Submitter\UrlSubmitterInterface;

/**
 * Replaces the built-in URL submitters, because it is tagged with a higher priority
 */
final class CustomUrlSubmitter implements UrlSubmitterInterface
{
    public function supports(int $numberOfStacks): bool
    {
        return true;
    }

    public function submit(string $host, array $stacks): bool
    {
        return true;
    }
}
