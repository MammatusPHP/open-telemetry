<?php

declare(strict_types=1);

namespace Mammatus\Tests\OpenTelemetry\WithSpan\Fixture;

final readonly class Payload
{
    public function __construct(
        public string $type,
        public string $file,
    ) {
    }
}
