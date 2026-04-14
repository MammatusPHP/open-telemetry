<?php

declare(strict_types=1);

namespace Mammatus\Tests\OpenTelemetry\Composer;

use Mammatus\OpenTelemetry\Composer\Collector;
use Mammatus\OpenTelemetry\Composer\Item;
use Mammatus\Tests\OpenTelemetry\Fixtures\WithoutReactAsync;
use Mammatus\Tests\OpenTelemetry\Fixtures\WithReactAsync;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\BetterReflection;
use WyriHaximus\Composer\GenerativePluginTooling\Item as ItemContract;

use function dirname;
use function iterator_to_array;

final class CollectorTest extends TestCase
{
    #[Test]
    public function collectYieldsItemWithRelativePathWhenFileUsesReactAsync(): void
    {
        $reflection = new BetterReflection()->reflector()->reflectClass(WithReactAsync::class);
        $items      = iterator_to_array(new Collector()->collect($reflection), false);

        self::assertCount(1, $items);
        self::assertInstanceOf(Item::class, $items[0]);
        self::assertSame(
            'tests/Fixtures/WithReactAsync.php',
            $items[0]->fileName,
        );
    }

    #[Test]
    public function collectYieldsNothingWhenFileDoesNotUseReactAsync(): void
    {
        $reflection = new BetterReflection()->reflector()->reflectClass(WithoutReactAsync::class);
        $items      = iterator_to_array(new Collector()->collect($reflection), false);

        self::assertSame([], $items);
    }

    #[Test]
    public function collectYieldsItemContract(): void
    {
        $reflection = new BetterReflection()->reflector()->reflectClass(WithReactAsync::class);
        $items      = iterator_to_array(new Collector()->collect($reflection), false);

        self::assertContainsOnlyInstancesOf(ItemContract::class, $items);
    }

    #[Test]
    public function collectUsesRelativePathNotAbsolutePath(): void
    {
        $reflection = new BetterReflection()->reflector()->reflectClass(WithReactAsync::class);
        $items      = iterator_to_array(new Collector()->collect($reflection), false);

        self::assertInstanceOf(Item::class, $items[0]);
        $fileName = $items[0]->fileName;
        self::assertStringStartsNotWith('/', $fileName);
        self::assertStringNotContainsString(dirname(__DIR__, 2), $fileName);
    }
}
