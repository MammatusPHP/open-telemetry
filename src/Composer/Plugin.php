<?php

declare(strict_types=1);

namespace Mammatus\OpenTelemetry\Composer;

use WyriHaximus\Composer\GenerativePluginTooling\Filter\Operators\LogicalOr;
use WyriHaximus\Composer\GenerativePluginTooling\Filter\Package\ComposerJsonRequiresSpecificPackage;
use WyriHaximus\Composer\GenerativePluginTooling\Filter\Package\PackageType;
use WyriHaximus\Composer\GenerativePluginTooling\GenerativePlugin;
use WyriHaximus\Composer\GenerativePluginTooling\Item as ItemContract;
use WyriHaximus\Composer\GenerativePluginTooling\LogStages;

use function file_get_contents;
use function file_put_contents;
use function str_replace;

final class Plugin implements GenerativePlugin
{
    public static function name(): string
    {
        return 'mammatus/open-telemetry';
    }

    public static function log(LogStages $stage): string
    {
        return match ($stage) {
            LogStages::Init => 'Locating files to monkey patch',
            LogStages::Error => 'An error occurred: %s',
            LogStages::Collected => 'Found %d file(s) to monkey patch',
            LogStages::Completion => 'Monkey Patched React\Async\async to Mammatus\OpenTelemetry\async in %s second(s)',
        };
    }

    /** @inheritDoc */
    public function filters(): iterable
    {
        yield from LogicalOr::create(
            new ComposerJsonRequiresSpecificPackage('react/async', PackageType::PRODUCTION),
            new ComposerJsonRequiresSpecificPackage('react/async', PackageType::DEVELOPMENT),
        );
    }

    /** @inheritDoc */
    public function collectors(): iterable
    {
        yield new Collector();
    }

    public function compile(string $rootPath, ItemContract ...$items): void
    {
        foreach ($items as $item) {
            if (! ($item instanceof Item)) {
                continue;
            }

            if ($item->fileName === 'src/Composer/Plugin.php') {
                continue;
            }

            if ($item->fileName === 'src/functions.php') {
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
