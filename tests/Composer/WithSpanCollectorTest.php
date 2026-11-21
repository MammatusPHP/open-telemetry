<?php

declare(strict_types=1);

namespace Mammatus\Tests\OpenTelemetry\Composer;

use Mammatus\OpenTelemetry\Composer\Item;
use Mammatus\OpenTelemetry\Composer\WithSpanCollector;
use Mammatus\Tests\OpenTelemetry\WithSpan\Fixture\ChildGreeter;
use Mammatus\Tests\OpenTelemetry\WithSpan\Fixture\Greeter;
use Mammatus\Tests\OpenTelemetry\WithSpan\Fixture\WithoutSpanOnly;
use PHPUnit\Framework\Attributes\Test;
use Roave\BetterReflection\BetterReflection;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;
use WyriHaximus\Composer\GenerativePluginTooling\Item as ItemContract;

use function iterator_to_array;

final class WithSpanCollectorTest extends AsyncTestCase
{
    #[Test]
    public function collectYieldsClassWhenMethodHasWithSpan(): void
    {
        $reflection = new BetterReflection()->reflector()->reflectClass(Greeter::class);
        $items      = iterator_to_array(new WithSpanCollector()->collect($reflection), false);

        self::assertCount(1, $items);
        self::assertInstanceOf(Item::class, $items[0]);
        self::assertSame(Greeter::class, $items[0]->class);
        self::assertContainsOnlyInstancesOf(ItemContract::class, $items);
    }

    #[Test]
    public function collectYieldsNothingWhenClassHasNoWithSpanMethods(): void
    {
        $reflection = new BetterReflection()->reflector()->reflectClass(WithoutSpanOnly::class);
        $items      = iterator_to_array(new WithSpanCollector()->collect($reflection), false);

        self::assertSame([], $items);
    }

    #[Test]
    public function collectSkipsInheritedWithSpanMethods(): void
    {
        $reflection = new BetterReflection()->reflector()->reflectClass(ChildGreeter::class);
        $items      = iterator_to_array(new WithSpanCollector()->collect($reflection), false);

        self::assertSame([], $items);
    }
}
