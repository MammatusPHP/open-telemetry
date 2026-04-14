<?php

declare(strict_types=1);

namespace Mammatus\OpenTelemetry\Fiber;

use Fiber;
use OpenTelemetry\Context\Context;
use React\Async\FiberInterface;
use React\Async\SimpleFiber;
use Throwable;

use function spl_object_id;

final class Observer implements FiberInterface
{
    /** @var ?Fiber<mixed,mixed,mixed,mixed> */
    private Fiber|null $fiber = null;

    public function __construct(
        private readonly SimpleFiber $simpleFiber,
    ) {
        $this->fiber = Fiber::getCurrent();
//        if ($this->fiber instanceof Fiber) {
//        Context::storage()->fork(spl_object_id($this->fiber));
//    }
    }

    public function resume(mixed $value): void
    {
        if ($this->fiber instanceof Fiber) {
            Context::storage()->switch(spl_object_id($this->fiber));
        }
        $this->simpleFiber->resume($value);
    }

    public function throw(Throwable $throwable): void
    {if ($this->fiber instanceof Fiber) {
        Context::storage()->switch(spl_object_id($this->fiber));
    }
        $this->simpleFiber->throw($throwable);
    }

    public function suspend(): mixed
    {
        return $this->simpleFiber->suspend();
    }
}
