<?php

declare(strict_types=1);

namespace Mammatus\OpenTelemetry\Composer;

use JsonSerializable;
use WyriHaximus\Composer\GenerativePluginTooling\Item as ItemContract;

final readonly class Item implements ItemContract, JsonSerializable
{
    public static function forFileName(string $fileName): self
    {
        return new self($fileName, null);
    }

    /** @param class-string $class */
    public static function forClass(string $class): self
    {
        return new self(null, $class);
    }

    public function __construct(
        public string|null $fileName,
        public string|null $class,
    ) {
    }

    /** @return array{fileName: string}|array{class: class-string} */
    public function jsonSerialize(): array
    {
        if ($this->fileName !== null) {
            return ['fileName' => $this->fileName];
        }

        /** @var class-string $class */
        $class = $this->class;

        return ['class' => $class];
    }
}
