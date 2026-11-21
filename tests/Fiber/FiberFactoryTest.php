<?php

declare(strict_types=1);

namespace Mammatus\Tests\OpenTelemetry\Fiber;

use FiberError;
use Mammatus\OpenTelemetry\Fiber\Factory;
use Mammatus\OpenTelemetry\Fiber\Observer;
use Mammatus\Tests\OpenTelemetry\OtelFibersEnabled;
use OpenTelemetry\Context\Context;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use React\Async\SimpleFiber;
use React\Promise\Deferred;
use RuntimeException;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;

use function Mammatus\OpenTelemetry\async;
use function range;
use function React\Async\await;
use function React\Promise\reject;
use function React\Promise\resolve;
use function React\Promise\Timer\sleep;

final class FiberFactoryTest extends AsyncTestCase
{
    use OtelFibersEnabled;

    #[Test]
    #[DataProvider('provideFibersEnabledCases')]
    public function fibersEnabledReadsEnvironment(string|false $value, bool $expected): void
    {
        $this->withFibersEnabledEnv($value, static function () use ($expected): void {
            self::assertSame($expected, Factory::fibersEnabled());
        });
    }

    /** @return iterable<string, array{string|false, bool}> */
    public static function provideFibersEnabledCases(): iterable
    {
        yield 'unset' => [false, false];
        yield 'empty' => ['', false];
        yield 'false' => ['false', false];
        yield 'true' => ['true', true];
        yield '1' => ['1', true];
        yield '0' => ['0', false];
    }

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
    #[DataProvider('provideObserverThrowCases')]
    public function observerThrowSwitchesContextBeforeDelegating(bool $zendFibers): void
    {
        $this->expectException(FiberError::class);

        $throw = static function (): void {
            await(
                async(static function (): void {
                    /** @phpstan-ignore new.internalClass, method.internalClass */
                    new Observer(new SimpleFiber())->throw(new RuntimeException('fiber error'));
                })(),
            );
        };

        if ($zendFibers) {
            $this->withFibersEnabledEnv('true', static function () use ($throw): void {
                Factory::init();
                $throw();
            });

            return;
        }

        Factory::init();
        $throw();
    }

    /** @return iterable<string, array{bool}> */
    public static function provideObserverThrowCases(): iterable
    {
        yield 'userland' => [false];
        yield 'zend' => [true];
    }

    #[Test]
    public function withSpanStyleAttachSurvivesManySuspends(): void
    {
        $this->withUserlandFibersInitialized(static function (): void {
            $key = Context::createKey('with-span-style');

            await(
                async(static function () use ($key): void {
                    foreach (range(1, 5) as $i) {
                        $scope = Context::getCurrent()->with($key, 'iteration-' . $i)->activate();

                        try {
                            await(sleep(0.001));
                            self::assertSame('iteration-' . $i, Context::getCurrent()->get($key));
                            await(sleep(0.001));
                            self::assertSame('iteration-' . $i, Context::getCurrent()->get($key));
                        } finally {
                            $scope->detach();
                        }
                    }
                })(),
            );
        });
    }

    #[Test]
    public function suspendedFiberKeepsAttachedScopeAcrossResume(): void
    {
        $this->withUserlandFibersInitialized(static function (): void {
            $key      = Context::createKey('resume-scope');
            $deferred = new Deferred();

            $promise = async(static function () use ($key, $deferred): string {
                $scope = Context::getCurrent()->with($key, 'attached')->activate();

                try {
                    await($deferred->promise());

                    /** @var string $value */
                    $value = Context::getCurrent()->get($key);

                    return $value;
                } finally {
                    $scope->detach();
                }
            })();

            $deferred->resolve(null);

            self::assertSame('attached', await($promise));
        });
    }

    #[Test]
    public function awaitFromSyncContext(): void
    {
        Factory::init();

        self::assertSame('ok', await(resolve('ok')));
    }

    #[Test]
    public function awaitRejectionFromSyncContext(): void
    {
        Factory::init();

        self::expectExceptionMessageIsOrContains('expected');

        await(reject(new RuntimeException('expected')));
    }

    #[Test]
    public function nestedAwaitFromSyncContext(): void
    {
        Factory::init();

        self::assertSame(
            'nested',
            await(resolve(null)->then(static fn (): string => await(resolve('nested')))),
        );
    }
}
