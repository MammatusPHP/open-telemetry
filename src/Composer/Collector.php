<?php

declare(strict_types=1);

namespace Mammatus\OpenTelemetry\Composer;

use Roave\BetterReflection\Reflection\ReflectionClass;
use WyriHaximus\Composer\GenerativePluginTooling\Item as ItemContract;
use WyriHaximus\Composer\GenerativePluginTooling\ItemCollector;

use function dirname;
use function file_exists;
use function file_get_contents;
use function str_contains;

final class Collector implements ItemCollector
{
    /** @return iterable<ItemContract> */
    public function collect(ReflectionClass $class): iterable
    {
        $fileName = $class->getFileName();
        if ($fileName === null) {
            return;
        }

        $functionsFile = dirname($fileName) . '/functions.php';
        /** @phpstan-ignore wyrihaximus.reactphp.blocking.function.fileExists */
        if (! file_exists($functionsFile)) {
            return;
        }

        /** @phpstan-ignore wyrihaximus.reactphp.blocking.function.fileGetContents */
        $contents = file_get_contents($functionsFile);
        if ($contents === false) {
            // @codeCoverageIgnoreStart
            return;
            // @codeCoverageIgnoreEnd
        }

        $alreadyPatched = str_contains($contents, 'async_original');

        yield new Item($functionsFile, $alreadyPatched);
    }
}
