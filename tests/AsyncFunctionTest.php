<?php

declare(strict_types=1);

namespace Mammatus\Tests\OpenTelemetry;

use OpenTelemetry\Context\Context;
use PHPUnit\Framework\Attributes\Test;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;

use function Mammatus\OpenTelemetry\async;
use function React\Async\await;

final class AsyncFunctionTest extends AsyncTestCase
{
    use OtelFibersEnabled;

    #[Test]
    public function asyncForksAndDestroysContextPerFiber(): void
    {
        $this->withUserlandFibersInitialized(static function (): void {
            $key    = Context::createKey('mammatus-test');
            $parent = Context::getCurrent()->with($key, 'parent');
            $scope  = $parent->activate();

            try {
                $promise = async(static function () use ($key): string {
                    self::assertSame('parent', Context::getCurrent()->get($key));

                    return 'done';
                })();

                self::assertSame('done', await($promise));
                self::assertSame('parent', Context::getCurrent()->get($key));
            } finally {
                $scope->detach();
            }
        });
    }

    #[Test]
    public function asyncForwardsArguments(): void
    {
        $this->withUserlandFibersInitialized(static function (): void {
            self::assertSame(5, await(async(static fn (int $left, int $right): int => $left + $right)(2, 3)));
        });
    }
}
