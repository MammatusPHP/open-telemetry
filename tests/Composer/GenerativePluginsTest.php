<?php

declare(strict_types=1);

namespace Mammatus\Tests\OpenTelemetry\Composer;

use Mammatus\OpenTelemetry\Composer\MonkeyPatcher;
use Mammatus\OpenTelemetry\Composer\WithSpanRegisterer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;
use WyriHaximus\Composer\GenerativePluginTooling\GenerativePlugin;

final class GenerativePluginsTest extends AsyncTestCase
{
    use AssertsGenerativePlugin;

    /** @return iterable<string, array{class-string<GenerativePlugin>, string, string, string}> */
    public static function providePlugins(): iterable
    {
        yield 'monkey-patcher' => [
            MonkeyPatcher::class,
            'Locating files to monkey patch',
            'Found %d file(s) to monkey patch',
            'Monkey Patched React\Async\async to Mammatus\OpenTelemetry\async in %s second(s)',
        ];

        yield 'with-span-registerer' => [
            WithSpanRegisterer::class,
            'Locating #[WithSpan] classes',
            'Found %d #[WithSpan] class(es)',
            'Generated WithSpan registrar in %s second(s)',
        ];
    }

    /** @param class-string<GenerativePlugin> $pluginClass */
    #[Test]
    #[DataProvider('providePlugins')]
    public function metadata(
        string $pluginClass,
        string $init,
        string $collected,
        string $completion,
    ): void {
        $this->assertGenerativePluginName($pluginClass);
        $this->assertGenerativePluginLog($pluginClass, $init, $collected, $completion);
        $this->assertGenerativePluginHasFiltersAndCollectors(new $pluginClass());
    }
}
