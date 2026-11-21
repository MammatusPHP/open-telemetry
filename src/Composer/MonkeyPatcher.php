<?php

declare(strict_types=1);

namespace Mammatus\OpenTelemetry\Composer;

use WyriHaximus\Composer\GenerativePluginTooling\GenerativePlugin;
use WyriHaximus\Composer\GenerativePluginTooling\Item as ItemContract;
use WyriHaximus\Composer\GenerativePluginTooling\LogStages;

use function file_get_contents;
use function file_put_contents;
use function in_array;
use function str_replace;

final class MonkeyPatcher implements GenerativePlugin
{
    private const array SKIP = [
        'src/Composer/MonkeyPatcher.php',
        'src/Composer/WithSpanRegisterer.php',
        'src/functions.php',
    ];

    public static function name(): string
    {
        return Plugin::name();
    }

    public static function log(LogStages $stage): string
    {
        return Plugin::stageLog(
            $stage,
            'Locating files to monkey patch',
            'Found %d file(s) to monkey patch',
            'Monkey Patched React\Async\async to Mammatus\OpenTelemetry\async in %s second(s)',
        );
    }

    /** @inheritDoc */
    public function filters(): iterable
    {
        yield from Plugin::packageFilters('react/async');
    }

    /** @inheritDoc */
    public function collectors(): iterable
    {
        yield new Collector();
    }

    public function compile(string $rootPath, ItemContract ...$items): void
    {
        foreach ($items as $item) {
            if (! ($item instanceof Item) || $item->fileName === null) {
                continue;
            }

            if (in_array($item->fileName, self::SKIP, true)) {
                continue;
            }

            $filePath = ProjectRoot::absolutePath($rootPath, $item->fileName);

            /** @phpstan-ignore wyrihaximus.reactphp.blocking.function.fileGetContents, ergebnis.noErrorSuppression */
            $fileContents = @file_get_contents($filePath);
            if ($fileContents === false) {
                continue;
            }

            $fileContents = str_replace(
                ['React\Async\async'],
                ['Mammatus\OpenTelemetry\async'],
                $fileContents,
            );

            /** @phpstan-ignore wyrihaximus.reactphp.blocking.function.filePutContents */
            file_put_contents($filePath, $fileContents);
        }
    }
}
