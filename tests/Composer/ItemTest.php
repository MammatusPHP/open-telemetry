<?php

declare(strict_types=1);

namespace Mammatus\Tests\OpenTelemetry\Composer;

use Mammatus\OpenTelemetry\Composer\Item;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ItemTest extends TestCase
{
    #[Test]
    public function jsonSerialize(): void
    {
        self::assertSame(
            ['fileName' => 'vendor/react/async/src/functions.php'],
            new Item('vendor/react/async/src/functions.php')->jsonSerialize(),
        );
    }
}
