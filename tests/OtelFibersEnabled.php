<?php

declare(strict_types=1);

namespace Mammatus\Tests\OpenTelemetry;

use Mammatus\OpenTelemetry\Fiber\Factory;

use function getenv;
use function putenv;

trait OtelFibersEnabled
{
    /** @param callable(): void $callback */
    private function withUserlandFiberTracking(callable $callback): void
    {
        $this->withFibersEnabledEnv('false', $callback);
    }

    /** @param callable(): void $callback */
    private function withUserlandFibersInitialized(callable $callback): void
    {
        $this->withUserlandFiberTracking(static function () use ($callback): void {
            Factory::init();
            $callback();
        });
    }

    /** @param callable(): void $callback */
    private function withFibersEnabledEnv(string|false $value, callable $callback): void
    {
        $previous = getenv('OTEL_PHP_FIBERS_ENABLED');
        putenv($value === false ? 'OTEL_PHP_FIBERS_ENABLED' : 'OTEL_PHP_FIBERS_ENABLED=' . $value);

        try {
            $callback();
        } finally {
            putenv($previous === false ? 'OTEL_PHP_FIBERS_ENABLED' : 'OTEL_PHP_FIBERS_ENABLED=' . $previous);
        }
    }
}
