<?php

declare(strict_types=1);

namespace Mammatus\Tests\OpenTelemetry\Composer;

use Mammatus\OpenTelemetry\Composer\Collector;
use Mammatus\OpenTelemetry\Composer\Item;
use Mammatus\Tests\OpenTelemetry\Fixtures\WithoutReactAsync;
use Mammatus\Tests\OpenTelemetry\Fixtures\WithReactAsync;
use PHPUnit\Framework\Attributes\Test;
use Roave\BetterReflection\BetterReflection;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;

use function dirname;
use function iterator_to_array;

final class CollectorTest extends AsyncTestCase
{
    #[Test]
    public function collectYieldsItemWithRelativePathWhenFileUsesReactAsync(): void
    {
        $reflection = new BetterReflection()->reflector()->reflectClass(WithReactAsync::class);
        $items      = iterator_to_array(new Collector()->collect($reflection), false);

        self::assertCount(1, $items);
        self::assertInstanceOf(Item::class, $items[0]);
        self::assertSame('tests/Fixtures/WithReactAsync.php', $items[0]->fileName);
        self::assertStringStartsNotWith('/', $items[0]->fileName);
        self::assertStringNotContainsString(dirname(__DIR__, 2), $items[0]->fileName);
    }

    #[Test]
    public function collectYieldsNothingWhenFileDoesNotUseReactAsync(): void
    {
        $reflection = new BetterReflection()->reflector()->reflectClass(WithoutReactAsync::class);
        $items      = iterator_to_array(new Collector()->collect($reflection), false);

        self::assertSame([], $items);
    }
}
