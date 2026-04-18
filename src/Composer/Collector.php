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

        $fileName     = $class->getFileName();
        $fileContents = file_get_contents($fileName);
        if (! str_contains($fileContents, 'React\Async')) {
            return;
        }

        yield new Item(
            $fileName,
        );
    }
}
