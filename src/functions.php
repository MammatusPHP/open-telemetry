<?php

declare(strict_types=1);

namespace Mammatus\OpenTelemetry;

use Fiber;
use OpenTelemetry\Context\Context;
use React\Promise\PromiseInterface;

use function spl_object_id;

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
    return \React\Async\async(
        static function (mixed ...$args) use ($function) {
            Context::storage()->fork(spl_object_id(Fiber::getCurrent()));
            try {
                return $function(...$args);
            } finally {
                Context::storage()->destroy(spl_object_id(Fiber::getCurrent()));
            }
        },
    );
}
