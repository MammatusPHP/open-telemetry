<?php

declare(strict_types=1);

namespace Mammatus\OpenTelemetry\Fiber;

use Fiber as PhpFiber;
use OpenTelemetry\Context\Context;
use React\Async\FiberInterface;
use React\Async\SimpleFiber;
use Throwable;

use function spl_object_id;

/**
 * Propagates OpenTelemetry context across ReactPHP fiber suspend/resume when
 * ZendObserverFiber is not active (OTEL_PHP_FIBERS_ENABLED=false).
 *
 * Matching {@see \Mammatus\OpenTelemetry\async()} forks once for the fiber lifetime.
 * This observer restores that fork on resume/throw only — it must not destroy the
 * fork on suspend (that dropped #[WithSpan] scopes and corrupted the Zend heap),
 * and must not switch to a blank main on suspend (that broke WithSpanHandler attach).
 *
 * @phpstan-ignore class.implementsInternalInterface
 */
final class Observer implements FiberInterface
{
    private int|null $contextKey = null;

    public function __construct(
        /** @phpstan-ignore parameter.internalClass,property.internalClass */
        private readonly SimpleFiber $simpleFiber,
    ) {
    }

    public function resume(mixed $value): void
    {
        $this->enterFiberContext();
        /** @phpstan-ignore method.internalClass */
        $this->simpleFiber->resume($value);
    }

    public function throw(Throwable $throwable): void
    {
        $this->enterFiberContext();
        /** @phpstan-ignore method.internalClass */
        $this->simpleFiber->throw($throwable);
    }

    public function suspend(): mixed
    {
        $fiber = PhpFiber::getCurrent();
        if ($fiber instanceof PhpFiber) {
            $this->contextKey = spl_object_id($fiber);
        }

        /** @phpstan-ignore method.internalClass */
        return $this->simpleFiber->suspend();
    }

    private function enterFiberContext(): void
    {
        if ($this->contextKey === null) {
            return;
        }

        Context::storage()->switch($this->contextKey);
    }
}
