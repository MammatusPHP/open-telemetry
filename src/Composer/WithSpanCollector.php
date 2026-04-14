<?php

declare(strict_types=1);

namespace Mammatus\OpenTelemetry\Composer;

use OpenTelemetry\API\Instrumentation\WithSpan;
use Roave\BetterReflection\Reflection\ReflectionAttribute;
use Roave\BetterReflection\Reflection\ReflectionClass;
use WyriHaximus\Composer\GenerativePluginTooling\Item as ItemContract;
use WyriHaximus\Composer\GenerativePluginTooling\ItemCollector;

use function array_map;
use function in_array;

final class WithSpanCollector implements ItemCollector
{
    /** @return iterable<ItemContract> */
    public function collect(ReflectionClass $class): iterable
    {
        foreach ($class->getMethods() as $method) {
            if ($method->getDeclaringClass()->getName() !== $class->getName()) {
                continue;
            }

            $attributeNames = array_map(
                static fn (ReflectionAttribute $attribute): string => $attribute->getName(),
                $method->getAttributes(),
            );
            if (! in_array(WithSpan::class, $attributeNames, true)) {
                continue;
            }

            yield Item::forClass($class->getName());

            return;
        }
    }
}
