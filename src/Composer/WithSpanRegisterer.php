<?php

declare(strict_types=1);

namespace Mammatus\OpenTelemetry\Composer;

use WyriHaximus\Composer\GenerativePluginTooling\GenerativePlugin;
use WyriHaximus\Composer\GenerativePluginTooling\Helper\Remove;
use WyriHaximus\Composer\GenerativePluginTooling\Helper\TwigFile;
use WyriHaximus\Composer\GenerativePluginTooling\Item as ItemContract;
use WyriHaximus\Composer\GenerativePluginTooling\LogStages;

use function array_values;
use function count;
use function ksort;

final class WithSpanRegisterer implements GenerativePlugin
{
    public static function name(): string
    {
        return Plugin::name();
    }

    public static function log(LogStages $stage): string
    {
        return Plugin::stageLog(
            $stage,
            'Locating #[WithSpan] classes',
            'Found %d #[WithSpan] class(es)',
            'Generated WithSpan registrar in %s second(s)',
        );
    }

    /** @inheritDoc */
    public function filters(): iterable
    {
        yield from Plugin::packageFilters('open-telemetry/api');
    }

    /** @inheritDoc */
    public function collectors(): iterable
    {
        yield new WithSpanCollector();
    }

    public function compile(string $rootPath, ItemContract ...$items): void
    {
        /** @var array<string, string> $withSpanClasses */
        $withSpanClasses = [];
        foreach ($items as $item) {
            if (! ($item instanceof Item) || $item->class === null) {
                continue;
            }

            /** @var class-string $class */
            $class                   = $item->class;
            $withSpanClasses[$class] = $class;
        }

        ksort($withSpanClasses);

        $registrarPath = $rootPath . '/src/WithSpan/Registrar.php';
        Remove::fileOnlyIfItExists($registrarPath);
        if (count($withSpanClasses) === 0) {
            return;
        }

        TwigFile::render(
            $rootPath . '/etc/generated_templates/WithSpanRegistrar.php.twig',
            $registrarPath,
            ['classes' => array_values($withSpanClasses)],
        );
    }
}
