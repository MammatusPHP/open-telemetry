<?php

declare(strict_types=1);

namespace Mammatus\Tests\OpenTelemetry\WithSpan;

use Mammatus\OpenTelemetry\WithSpan\Hooks;
use Mammatus\Tests\OpenTelemetry\WithInMemoryTracer;
use Mammatus\Tests\OpenTelemetry\WithSpan\Fixture\ChildGreeter;
use Mammatus\Tests\OpenTelemetry\WithSpan\Fixture\Greeter;
use Mammatus\Tests\OpenTelemetry\WithSpan\Fixture\Named;
use Mammatus\Tests\OpenTelemetry\WithSpan\Fixture\Payload;
use Mammatus\Tests\OpenTelemetry\WithSpan\Fixture\Plain;
use OpenTelemetry\SDK\Trace\SpanDataInterface;
use OpenTelemetry\SDK\Trace\SpanExporter\InMemoryExporter;
use OpenTelemetry\SDK\Trace\TracerProvider;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;

use function array_key_last;

final class HooksTest extends AsyncTestCase
{
    use WithInMemoryTracer;

    #[Test]
    public function registerClassCreatesSpanForWithSpanMethod(): void
    {
        $this->withTracer(static function (InMemoryExporter $exporter, TracerProvider $tracerProvider): void {
            Hooks::registerClass(Greeter::class);

            self::assertSame('hello-world', new Greeter()->hello('world'));

            $tracerProvider->forceFlush();
            /** @var list<SpanDataInterface> $spans */
            $spans = $exporter->getSpans();
            self::assertCount(1, $spans);
            self::assertSame(Greeter::class . '::hello', $spans[0]->getName());
            self::assertSame('world', $spans[0]->getAttributes()->get('name'));
        });
    }

    /** @param array<string, mixed> $expectedAttributes */
    #[Test]
    #[DataProvider('provideAttributeCases')]
    public function registerClassFlattensSpanAttributes(callable $invoke, string $expectedSpanSuffix, array $expectedAttributes): void
    {
        $this->withTracer(static function (InMemoryExporter $exporter, TracerProvider $tracerProvider) use ($invoke, $expectedSpanSuffix, $expectedAttributes): void {
            Hooks::registerClass(Greeter::class);
            $invoke(new Greeter());

            $tracerProvider->forceFlush();
            /** @var list<SpanDataInterface> $spans */
            $spans = $exporter->getSpans();
            self::assertNotCount(0, $spans);
            $span = $spans[array_key_last($spans)];
            self::assertSame(Greeter::class . '::' . $expectedSpanSuffix, $span->getName());
            foreach ($expectedAttributes as $key => $value) {
                self::assertSame($value, $span->getAttributes()->get($key));
            }
        });
    }

    /** @return iterable<string, array{callable(Greeter): void, string, array<string, mixed>}> */
    public static function provideAttributeCases(): iterable
    {
        yield 'payload' => [
            static function (Greeter $greeter): void {
                $greeter->withPayload(new Payload('sea', 'a.jpg'));
            },
            'withPayload',
            ['payload.type' => 'sea', 'payload.file' => 'a.jpg'],
        ];

        yield 'named' => [
            static function (Greeter $greeter): void {
                $greeter->withNamed(new Named('x'));
            },
            'withNamed',
            ['named' => 'x'],
        ];

        yield 'plain' => [
            static function (Greeter $greeter): void {
                $greeter->withPlain(new Plain());
            },
            'withPlain',
            ['plain' => Plain::class],
        ];

        yield 'list' => [
            static function (Greeter $greeter): void {
                $greeter->withList(['a']);
            },
            'withList',
            ['items' => '[array]'],
        ];

        yield 'mixed' => [
            static function (Greeter $greeter): void {
                $greeter->mixedParams('skip', 'kept');
            },
            'mixedParams',
            ['name' => 'kept'],
        ];
    }

    #[Test]
    public function registerClassDoesNotHookMethodsWithoutWithSpan(): void
    {
        $this->withTracer(static function (InMemoryExporter $exporter, TracerProvider $tracerProvider): void {
            Hooks::registerClass(Greeter::class);

            self::assertSame('world', new Greeter()->withoutSpan('world'));

            $tracerProvider->forceFlush();
            self::assertCount(0, $exporter->getSpans());
        });
    }

    #[Test]
    public function registerClassIsIdempotent(): void
    {
        $this->withTracer(static function (InMemoryExporter $exporter, TracerProvider $tracerProvider): void {
            Hooks::registerClass(Greeter::class);
            Hooks::registerClass(Greeter::class);

            self::assertSame('hello-world', new Greeter()->hello('world'));

            $tracerProvider->forceFlush();
            self::assertCount(1, $exporter->getSpans());
        });
    }

    #[Test]
    public function registerClassSkipsInheritedMethods(): void
    {
        $this->withTracer(static function (InMemoryExporter $exporter, TracerProvider $tracerProvider): void {
            Hooks::registerClass(ChildGreeter::class);
            Hooks::registerClass(Greeter::class);

            self::assertSame('hello-world', new ChildGreeter()->hello('world'));

            $tracerProvider->forceFlush();
            self::assertCount(1, $exporter->getSpans());
        });
    }

    #[Test]
    public function registerClassIgnoresUnknownClass(): void
    {
        $this->expectNotToPerformAssertions();

        /** @phpstan-ignore argument.type */
        Hooks::registerClass('Mammatus\\Tests\\OpenTelemetry\\WithSpan\\Fixture\\DoesNotExist');
    }
}
