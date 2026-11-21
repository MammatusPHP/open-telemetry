<?php

declare(strict_types=1);

namespace Mammatus\OpenTelemetry\Composer;

use JsonSerializable;
use WyriHaximus\Composer\GenerativePluginTooling\Item as ItemContract;

final readonly class WithSpanItem implements ItemContract, JsonSerializable
{
    public function __construct(
        /** @var class-string */
        public string $class,
    ) {
    }

    /** @return array{class: class-string} */
    public function jsonSerialize(): array
    {
        return ['class' => $this->class];
    }
}
