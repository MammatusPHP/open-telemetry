<?php

declare(strict_types=1);

namespace Mammatus\OpenTelemetry\Composer;

use Roave\BetterReflection\Reflection\ReflectionClass;
use WyriHaximus\Composer\GenerativePluginTooling\Item as ItemContract;
use WyriHaximus\Composer\GenerativePluginTooling\ItemCollector;

use function file_get_contents;
use function str_contains;

final class Collector implements ItemCollector
{
    /** @return iterable<ItemContract> */
    public function collect(ReflectionClass $class): iterable
    {
        yield from [];

        $fileName = $class->getFileName();
        if ($fileName === null) { // @codeCoverageIgnoreStart
            return;
        } // @codeCoverageIgnoreEnd

        /** @phpstan-ignore wyrihaximus.reactphp.blocking.function.fileGetContents */
        $fileContents = file_get_contents($fileName);
        if ($fileContents === false || ! str_contains($fileContents, 'React\Async')) { // @codeCoverageIgnoreStart
            return;
        } // @codeCoverageIgnoreEnd

        yield Item::forFileName(ProjectRoot::relativePath($fileName));
    }
}
