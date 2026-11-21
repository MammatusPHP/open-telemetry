<?php

declare(strict_types=1);

namespace Mammatus\OpenTelemetry\Composer;

use WyriHaximus\Composer\GenerativePluginTooling\GenerativePlugin;
use WyriHaximus\Composer\GenerativePluginTooling\Item as ItemContract;
use WyriHaximus\Composer\GenerativePluginTooling\LogStages;

use function file_get_contents;
use function file_put_contents;
use function str_replace;

final class MonkeyPatcher implements GenerativePlugin
{
    public static function name(): string
    {
        return Plugin::name();
    }

    public static function log(LogStages $stage): string
    {
        return Plugin::stageLog(
            $stage,
            'Locating react/async functions file',
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
            if (! ($item instanceof Item)) {
                continue;
            }

            if ($item->alreadyPatched) {
                continue;
            }

            /** @phpstan-ignore wyrihaximus.reactphp.blocking.function.fileGetContents, ergebnis.noErrorSuppression */
            $fileContents = @file_get_contents($item->path);
            if ($fileContents === false) { // @codeCoverageIgnore
                continue;
            }

            $fileContents = str_replace(
                'function async(callable $function): callable',
                'function async_original(callable $function): callable',
                $fileContents,
            );

            $wrapper = <<<'PHP'

/**
 * @template T
 * @template A1 (any number of function arguments, see https://github.com/phpstan/phpstan/issues/8214)
 * @template A2
 * @template A3
 * @template A4
 * @template A5
 * @param callable(A1,A2,A3,A4,A5): (PromiseInterface<T>|T) $function
 * @return callable(A1=,A2=,A3=,A4=,A5=): PromiseInterface<T>
 * @since 4.0.0
 * @see async_original()
 */
function async(callable $function): callable
{
    return \Mammatus\OpenTelemetry\async($function);
}
PHP;

            $fileContents .= $wrapper;

            /** @phpstan-ignore wyrihaximus.reactphp.blocking.function.filePutContents */
            file_put_contents($item->path, $fileContents);
        }
    }
}
