<?php

declare(strict_types=1);

namespace Mammatus\Tests\OpenTelemetry\Composer;

use Mammatus\OpenTelemetry\Composer\Item;
use PHPUnit\Framework\Attributes\Test;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;

final class ItemTest extends AsyncTestCase
{
    #[Test]
    public function createsItem(): void
    {
        $item = new Item('/path/to/functions.php', false);

        self::assertSame('/path/to/functions.php', $item->path);
        self::assertFalse($item->alreadyPatched);
    }

    #[Test]
    public function jsonSerialize(): void
    {
        $item = new Item('/path/to/functions.php', true);

        self::assertSame(
            ['path' => '/path/to/functions.php', 'alreadyPatched' => true],
            $item->jsonSerialize(),
        );
    }
}
