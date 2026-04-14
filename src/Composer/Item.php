<?php

declare(strict_types=1);

namespace Mammatus\OpenTelemetry\Composer;

use JsonSerializable;
use WyriHaximus\Composer\GenerativePluginTooling\Item as ItemContract;

final readonly class Item implements ItemContract, JsonSerializable
{
    /** @param class-string $class */
    public function __construct(
        public string $fileName,
    ) {
    }

    /** @return array{fileName: string} */
    public function jsonSerialize(): array
    {
        return [
            'fileName' => $this->fileName,
        ];
    }
}
