<?php

declare(strict_types=1);

namespace Mammatus\OpenTelemetry\Composer;

use JsonSerializable;
use WyriHaximus\Composer\GenerativePluginTooling\Item as ItemContract;

final readonly class Item implements ItemContract, JsonSerializable
{
    public function __construct(
        public string $path,
        public bool $alreadyPatched,
    ) {
    }

    /** @return array{path: string, alreadyPatched: bool} */
    public function jsonSerialize(): array
    {
        return [
            'path' => $this->path,
            'alreadyPatched' => $this->alreadyPatched,
        ];
    }
}
