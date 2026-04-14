<?php

declare(strict_types=1);

namespace Mammatus\Tests\OpenTelemetry\Composer;

use Mammatus\OpenTelemetry\Composer\Item;
use PHPUnit\Framework\Attributes\Test;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;

final class ItemTest extends AsyncTestCase
{
    #[Test]
    public function jsonSerializeFileName(): void
    {
        self::assertSame(
            ['fileName' => 'vendor/react/async/src/functions.php'],
            Item::forFileName('vendor/react/async/src/functions.php')->jsonSerialize(),
        );
    }

    #[Test]
    public function jsonSerializeClass(): void
    {
        self::assertSame(
            ['class' => self::class],
            Item::forClass(self::class)->jsonSerialize(),
        );
    }
}
