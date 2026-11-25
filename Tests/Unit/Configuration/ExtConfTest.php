<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/indexnow.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\IndexNow\Tests\Unit\Configuration;

use JWeiland\IndexNow\Configuration\ExtConf;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ExtConfTest extends UnitTestCase
{
    #[Test]
    public function createReadsSettingsFromExtensionConfiguration(): void
    {
        $extensionConfigurationMock = $this->createMock(ExtensionConfiguration::class);
        $extensionConfigurationMock
            ->expects(self::once())
            ->method('get')
            ->with('indexnow')
            ->willReturn(['searchEngineEndpoint' => ' https://api.indexnow.org/indexnow ', 'notifyBatchMode' => '1']);

        $subject = ExtConf::create($extensionConfigurationMock);

        self::assertSame('https://api.indexnow.org/indexnow', $subject->getSearchEngineEndpoint());
        self::assertTrue($subject->isNotifyBatchMode());
    }

    #[Test]
    public function createFallsBackToDefaultsIfExtensionIsNotConfigured(): void
    {
        $extensionConfigurationStub = self::createStub(ExtensionConfiguration::class);
        $extensionConfigurationStub
            ->method('get')
            ->willThrowException(new ExtensionConfigurationExtensionNotConfiguredException('', 1));

        $subject = ExtConf::create($extensionConfigurationStub);

        self::assertSame('https://www.bing.com/indexnow', $subject->getSearchEngineEndpoint());
        self::assertFalse($subject->isNotifyBatchMode());
    }

    #[Test]
    public function createFallsBackToDefaultsForInvalidConfiguration(): void
    {
        $extensionConfigurationStub = self::createStub(ExtensionConfiguration::class);
        $extensionConfigurationStub->method('get')->willReturn('invalid');

        $subject = ExtConf::create($extensionConfigurationStub);

        self::assertSame('https://www.bing.com/indexnow', $subject->getSearchEngineEndpoint());
        self::assertFalse($subject->isNotifyBatchMode());
    }

    #[Test]
    public function createFallsBackToDefaultEndpointIfFieldHasBeenEmptied(): void
    {
        $extensionConfigurationStub = self::createStub(ExtensionConfiguration::class);
        $extensionConfigurationStub
            ->method('get')
            ->willReturn(['searchEngineEndpoint' => '  ', 'notifyBatchMode' => '0']);

        $subject = ExtConf::create($extensionConfigurationStub);

        self::assertSame('https://www.bing.com/indexnow', $subject->getSearchEngineEndpoint());
        self::assertFalse($subject->isNotifyBatchMode());
    }

    #[Test]
    public function createIgnoresSettingsOfInvalidType(): void
    {
        $extensionConfigurationStub = self::createStub(ExtensionConfiguration::class);
        $extensionConfigurationStub
            ->method('get')
            ->willReturn(['searchEngineEndpoint' => ['invalid'], 'notifyBatchMode' => ['invalid']]);

        $subject = ExtConf::create($extensionConfigurationStub);

        self::assertSame('https://www.bing.com/indexnow', $subject->getSearchEngineEndpoint());
        self::assertFalse($subject->isNotifyBatchMode());
    }
}
