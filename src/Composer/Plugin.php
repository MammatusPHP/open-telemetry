<?php

declare(strict_types=1);

namespace Mammatus\OpenTelemetry\Composer;

use WyriHaximus\Composer\GenerativePluginTooling\ClassFilter;
use WyriHaximus\Composer\GenerativePluginTooling\Filter\Operators\LogicalOr;
use WyriHaximus\Composer\GenerativePluginTooling\Filter\Package\ComposerJsonRequiresSpecificPackage;
use WyriHaximus\Composer\GenerativePluginTooling\Filter\Package\PackageType;
use WyriHaximus\Composer\GenerativePluginTooling\LogStages;
use WyriHaximus\Composer\GenerativePluginTooling\PackageFilter;

final class Plugin
{
    public static function name(): string
    {
        return 'mammatus/open-telemetry';
    }

    public static function stageLog(LogStages $stage, string $init, string $collected, string $completion): string
    {
        return match ($stage) {
            LogStages::Init => $init,
            LogStages::Error => 'An error occurred: %s',
            LogStages::Collected => $collected,
            LogStages::Completion => $completion,
        };
    }

    /** @return iterable<ClassFilter|PackageFilter> */
    public static function packageFilters(string $package): iterable
    {
        yield from LogicalOr::create(
            new ComposerJsonRequiresSpecificPackage($package, PackageType::PRODUCTION),
            new ComposerJsonRequiresSpecificPackage($package, PackageType::DEVELOPMENT),
        );
    }
}
