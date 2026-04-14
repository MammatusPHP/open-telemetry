<?php

declare(strict_types=1);

namespace Mammatus\Tests\OpenTelemetry\Fiber;

use Mammatus\OpenTelemetry\WithSpan\Hooks;
use Mammatus\Tests\OpenTelemetry\OtelFibersEnabled;
use Mammatus\Tests\OpenTelemetry\WithInMemoryTracer;
use Mammatus\Tests\OpenTelemetry\WithSpan\Fixture\Greeter;
use OpenTelemetry\SDK\Trace\SpanDataInterface;
use OpenTelemetry\SDK\Trace\SpanExporter\InMemoryExporter;
use OpenTelemetry\SDK\Trace\TracerProvider;
use PHPUnit\Framework\Attributes\Test;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;

use function Mammatus\OpenTelemetry\async;
use function React\Async\await;
use function React\Promise\Timer\sleep;

final class AsyncWithSpanIntegrationTest extends AsyncTestCase
{
    use OtelFibersEnabled;
    use WithInMemoryTracer;

    #[Test]
    public function asyncWithSpanExportsSpanAfterFiberSuspend(): void
    {
        $this->withUserlandFibersInitialized(function (): void {
            $this->withTracer(static function (InMemoryExporter $exporter, TracerProvider $tracerProvider): void {
                Hooks::registerClass(Greeter::class);

                $result = await(
                    async(static function (): string {
                        await(sleep(0.001));

                        return new Greeter()->hello('fiber');
                    })(),
                );

                self::assertSame('hello-fiber', $result);

                $tracerProvider->forceFlush();
                /** @var list<SpanDataInterface> $spans */
                $spans = $exporter->getSpans();
                self::assertCount(1, $spans);
                self::assertSame(Greeter::class . '::hello', $spans[0]->getName());
                self::assertSame('fiber', $spans[0]->getAttributes()->get('name'));
            });
        });
    }
}
