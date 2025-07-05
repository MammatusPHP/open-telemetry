<?php

declare(strict_types=1);

namespace Mammatus\OpenTelemetry;

use Mammatus\LifeCycleEvents\Initialize;
use Mammatus\LifeCycleEvents\Shutdown;
use OpenTelemetry\API\Common\Time\Clock;
use OpenTelemetry\Contrib\Otlp\LogsExporterFactory;
use OpenTelemetry\Contrib\Otlp\MetricExporterFactory;
use OpenTelemetry\Contrib\Otlp\SpanExporterFactory;
use OpenTelemetry\SDK\Common\Configuration\Configuration;
use OpenTelemetry\SDK\Common\Configuration\Variables;
use OpenTelemetry\SDK\Common\Instrumentation\InstrumentationScopeFactory;
use OpenTelemetry\SDK\Logs\LoggerProvider;
use OpenTelemetry\SDK\Logs\LoggerProviderInterface;
use OpenTelemetry\SDK\Logs\LogRecordLimitsBuilder;
use OpenTelemetry\SDK\Logs\LogRecordProcessorFactory;
use OpenTelemetry\SDK\Metrics\Exemplar\ExemplarFilter\AllExemplarFilter;
use OpenTelemetry\SDK\Metrics\MeterProvider;
use OpenTelemetry\SDK\Metrics\MeterProviderInterface;
use OpenTelemetry\SDK\Metrics\MetricReader\ExportingReader;
use OpenTelemetry\SDK\Propagation\PropagatorFactory;
use OpenTelemetry\SDK\Resource\ResourceInfoFactory;
use OpenTelemetry\SDK\Sdk;
use OpenTelemetry\SDK\Trace\SpanProcessor\BatchSpanProcessor;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use OpenTelemetry\SDK\Trace\TracerProviderInterface;
use React\EventLoop\Loop;
use React\Http\Browser;
use WyriHaximus\Broadcast\Contracts\AsyncListener;

use function React\Async\async;
use function React\Async\await;

final class Otlp implements AsyncListener
{

//    private array $timers = [];

    /** @phpstan-ignore shipmonk.deadMethod */
    public function initialize(Initialize $initialize): void
    {
        echo '[OTEL] Initializing Otlp...' . PHP_EOL;

//        $this->timers[] = Loop::addPeriodicTimer(1, async(function (): bool {
//            try {
//                return $this->tracerProvider->forceFlush();
//            } catch (\Throwable $exception) {
//                echo $exception;
//
//                return false;
//            }
//        }));
//        $this->timers[] = Loop::addPeriodicTimer(1, async(function (): bool {
//            try {
//                return $this->loggerProvider->forceFlush();
//            } catch (\Throwable $exception) {
//                echo $exception;
//
//                return false;
//            }
//        }));
//        $this->timers[] = Loop::addPeriodicTimer(1, async(function (): bool {
//            try {
//                return $this->meterProvider->forceFlush();
//            } catch (\Throwable $exception) {
//                echo $exception;
//
//                return false;
//            }
//        }));
    }

    /** @phpstan-ignore shipmonk.deadMethod */
    public function shutdown(Shutdown $shutdown): void
    {
        echo '[OTEL] Shutting down Otlp...' . PHP_EOL;
//        foreach ($this->timers as $timer) {
//            Loop::cancelTimer($timer);
//        }
//        await(\React\Promise\Timer\sleep(1));
//        $this->tracerProvider->shutdown();
//        $this->loggerProvider->shutdown();
//        $this->meterProvider->shutdown();
    }
}
