<?php

declare(strict_types=1);

namespace Mammatus\OpenTelemetry\Fiber;

use Fiber;
use OpenTelemetry\Context\Context;
use React\Async\FiberInterface;
use React\Async\SimpleFiber;
use Throwable;

use function assert;
use function spl_object_id;

/** @phpstan-ignore class.implementsInternalInterface */
final readonly class Observer implements FiberInterface
{
    public function __construct(
        /** @phpstan-ignore parameter.internalClass,property.internalClass */
        private SimpleFiber $simpleFiber,
    ) {
    }

    public function resume(mixed $value): void
    {
        $this->switchContext();

        /** @phpstan-ignore method.internalClass */
        $this->simpleFiber->resume($value);
    }

    public function throw(Throwable $throwable): void
    {
        $this->switchContext();

        /** @phpstan-ignore method.internalClass */
        $this->simpleFiber->throw($throwable);
    }

    public function suspend(): mixed
    {
        /** @phpstan-ignore method.internalClass */
        return $this->simpleFiber->suspend();
    }

    private function switchContext(): void
    {
        $fiber = Fiber::getCurrent();
        assert($fiber instanceof Fiber);
        Context::storage()->switch(spl_object_id($fiber));
    }
}
