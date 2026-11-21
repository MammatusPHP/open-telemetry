<?php

declare(strict_types=1);

namespace Mammatus\OpenTelemetry;

use Fiber;
use Mammatus\OpenTelemetry\Fiber\Factory;
use OpenTelemetry\Context\Context;
use React\Promise\PromiseInterface;

use function define;
use function defined;
use function function_exists;
use function spl_object_id;

/**
 * Context storage key for the main (non-fiber) head.
 * Used by {@see async()} when tearing down a fiber-local fork.
 */
// Guard against Composer re-requiring this file while updating this plugin.
if (! defined(__NAMESPACE__ . '\\MAIN_CONTEXT_KEY')) {
    define(__NAMESPACE__ . '\\MAIN_CONTEXT_KEY', "\0mammatus-otel-main");
}

// Guard against Composer re-requiring this file while updating this plugin.
if (! function_exists(__NAMESPACE__ . '\\async')) {
    /**
     * @see https://reactphp.org/async/#async
     *
     * @param callable(A1,A2,A3,A4,A5): (PromiseInterface<T>|T) $function
     *
     * @return callable(A1=,A2=,A3=,A4=,A5=): PromiseInterface<T>
     *
     * @template T
     * @template A1 (any number of function arguments, see https://github.com/phpstan/phpstan/issues/8214)
     * @template A2
     * @template A3
     * @template A4
     * @template A5
     */
    function async(callable $function): callable
    {
        if (Factory::fibersEnabled()) {
            return \React\Async\async($function);
        }

        return \React\Async\async(
            static function (mixed ...$args) use ($function): mixed {
                $fiber = Fiber::getCurrent();
                if (! ($fiber instanceof Fiber)) {
                    return $function(...$args);
                }

                $contextKey = spl_object_id($fiber);
                $storage    = Context::storage();
                $storage->fork($contextKey);
                $storage->switch($contextKey);

                try {
                    return $function(...$args);
                } finally {
                    $storage->switch(MAIN_CONTEXT_KEY);
                    $storage->destroy($contextKey);
                }
            },
        );
    }
}
