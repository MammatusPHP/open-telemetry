<?php

declare(strict_types=1);

namespace Mammatus\Tests\OpenTelemetry\WithSpan\Fixture;

final class WithoutSpanOnly
{
    /** @phpstan-ignore shipmonk.deadMethod */
    public function hello(string $name): string
    {
        return $name;
    }
}
