<?php

declare(strict_types=1);

namespace Mammatus\Tests\OpenTelemetry\Fiber;

use FiberError;
use Mammatus\OpenTelemetry\Fiber\Factory;
use Mammatus\OpenTelemetry\Fiber\Observer;
use OpenTelemetry\Context\Context;
use PHPUnit\Framework\Attributes\Test;
use React\Async\SimpleFiber;
use RuntimeException;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;

use function Mammatus\OpenTelemetry\async;
use function React\Async\await;

final class FiberFactoryTest extends AsyncTestCase
{
    #[Test]
    public function initRegistersFiberFactory(): void
    {
        Factory::init();

        $key = Context::createKey('fiber-factory');

        self::assertSame(
            'inside',
            await(
                async(static function () use ($key): string {
                    $scope = Context::getCurrent()->with($key, 'inside')->activate();

                    try {
                        /** @var string $value */
                        $value = Context::getCurrent()->get($key);

                        return $value;
                    } finally {
                        $scope->detach();
                    }
                })(),
            ),
        );
    }

    #[Test]
    public function observerThrowSwitchesContextBeforeDelegating(): void
    {
        Factory::init();

        $this->expectException(FiberError::class);

        await(
            async(static function (): void {
                /** @phpstan-ignore new.internalClass, method.internalClass */
                new Observer(new SimpleFiber())->throw(new RuntimeException('fiber error'));
            })(),
        );
    }
}
