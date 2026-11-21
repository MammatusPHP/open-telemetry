<?php

declare(strict_types=1);

namespace Mammatus\Tests\OpenTelemetry\Composer;

use WyriHaximus\Composer\GenerativePluginTooling\GenerativePlugin;
use WyriHaximus\Composer\GenerativePluginTooling\LogStages;

trait AssertsGenerativePlugin
{
    /** @param class-string<GenerativePlugin> $pluginClass */
    protected function assertGenerativePluginName(string $pluginClass): void
    {
        self::assertSame('mammatus/open-telemetry', $pluginClass::name());
    }

    /** @param class-string<GenerativePlugin> $pluginClass */
    protected function assertGenerativePluginLog(
        string $pluginClass,
        string $init,
        string $collected,
        string $completion,
    ): void {
        self::assertSame($init, $pluginClass::log(LogStages::Init));
        self::assertSame('An error occurred: %s', $pluginClass::log(LogStages::Error));
        self::assertSame($collected, $pluginClass::log(LogStages::Collected));
        self::assertSame($completion, $pluginClass::log(LogStages::Completion));
    }

    /** @param int<0, max> $expectedCollectors */
    protected function assertGenerativePluginHasFiltersAndCollectors(GenerativePlugin $plugin, int $expectedCollectors = 1): void
    {
        self::assertNotEmpty([...$plugin->filters()]);
        self::assertCount($expectedCollectors, [...$plugin->collectors()]);
    }
}
