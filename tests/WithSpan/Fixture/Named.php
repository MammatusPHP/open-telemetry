<?php

declare(strict_types=1);

namespace Mammatus\Tests\OpenTelemetry\WithSpan\Fixture;

use Stringable;

final readonly class Named implements Stringable
{
    public function __construct(private string $value)
    {
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
