<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\IndexNow\Tests\Functional\Service;

use JWeiland\IndexNow\Service\ContentChangeDetector;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ContentChangeDetectorTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'jweiland/indexnow',
    ];

    /**
     * @return array<string, array{string, array<string, mixed>, bool}>
     */
    public static function changedFieldsDataProvider(): array
    {
        return [
            'nothing changed' => ['pages', [], false],
            'only updated at field' => ['pages', ['tstamp' => 1764000000], false],
            'only translation bookkeeping' => [
                'pages',
                ['l10n_state' => '{}', 'l10n_diffsource' => '{}', 'tstamp' => 1764000000],
                false,
            ],
            'only MM relation field (always passed by DataHandler)' => [
                'pages',
                ['categories' => '0', 'tstamp' => 1764000000],
                false,
            ],
            'content field' => ['pages', ['title' => 'Changed', 'tstamp' => 1764000000], true],
            'content field and MM relation field' => ['pages', ['title' => 'Changed', 'categories' => '0'], true],
            'synchronized l10n_mode=exclude field' => ['pages', ['layout' => 1, 'l10n_state' => '{}'], true],
            'table without TCA' => ['tx_unknown_table', ['tstamp' => 1764000000], true],
        ];
    }

    /**
     * @param array<string, mixed> $changedFields
     */
    #[Test]
    #[DataProvider('changedFieldsDataProvider')]
    public function hasContentChangesIgnoresBookkeepingFields(string $table, array $changedFields, bool $expected): void
    {
        $subject = new ContentChangeDetector($this->get(TcaSchemaFactory::class));

        self::assertSame($expected, $subject->hasContentChanges($table, $changedFields));
    }
}
