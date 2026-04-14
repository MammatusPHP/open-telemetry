<?php

declare(strict_types=1);

namespace Mammatus\OpenTelemetry\Fiber;

use OpenTelemetry\Context\Context;
use React\Async\FiberFactory;
use React\Async\FiberInterface;
use React\Async\SimpleFiber;

final class Factory
{
    public static function init(): void
    {
        Context::storage()->current();
        FiberFactory::factory(static fn (): FiberInterface => new Observer(new SimpleFiber()));
    }
}
