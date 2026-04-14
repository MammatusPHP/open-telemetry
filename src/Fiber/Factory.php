<?php

declare(strict_types=1);

namespace Mammatus\OpenTelemetry\Fiber;

use OpenTelemetry\Context\Context;
use OpenTelemetry\Context\ContextStorage;
use React\Async\FiberFactory;
use React\Async\FiberInterface;
use React\Async\SimpleFiber;

use function filter_var;
use function getenv;

use const FILTER_VALIDATE_BOOLEAN;

final class Factory
{
    public static function init(): void
    {
        if (self::fibersEnabled()) {
            // @codeCoverageIgnoreStart — ZendObserverFiber owns context; Observer is not registered.
            return;
            // @codeCoverageIgnoreEnd
        }

        // FiberBoundContextStorageExecutionAwareBC abandons fiber-local scopes on the
        // first fork(). Use explicit ContextStorage for userland React fiber tracking.
        /** @phpstan-ignore new.internalClass,method.internalClass */
        Context::setStorage(new ContextStorage());

        Context::storage()->current();

        /** @phpstan-ignore staticMethod.internalClass,return.internalInterface,new.internalClass,method.internalClass */
        FiberFactory::factory(static fn (): FiberInterface => new Observer(new SimpleFiber()));
    }

    /** @see https://opentelemetry.io/docs/languages/php/ */
    public static function fibersEnabled(): bool
    {
        $value = getenv('OTEL_PHP_FIBERS_ENABLED');

        return $value !== false && $value !== '' && filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
