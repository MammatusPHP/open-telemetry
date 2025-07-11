<?php

declare(strict_types=1);

namespace Mammatus\OpenTelemetry;

use Mammatus\LifeCycleEvents\Kernel;
use Mammatus\LifeCycleEvents\Shutdown;
use OpenTelemetry\Contrib\Otlp\LogsExporterFactory;
use OpenTelemetry\Contrib\Otlp\MetricExporterFactory;
use OpenTelemetry\Contrib\Otlp\SpanExporterFactory;
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
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use OpenTelemetry\SDK\Trace\TracerProviderInterface;
use React\EventLoop\Loop;
use React\Http\Browser;
use WyriHaximus\Broadcast\Contracts\AsyncListener;

use function React\Async\async;

final readonly class Otlp implements AsyncListener
{
    private LoggerProviderInterface $loggerProvider;
    private TracerProviderInterface $tracerProvider;
    private MeterProviderInterface $meterProvider;

    /** @phpstan-ignore shipmonk.deadMethod */
    public function __construct(Browser $browser)
    {
        $transportFactory     = new OtlpHttpTransportFactory($browser);
        $spanExporter         = (new SpanExporterFactory($transportFactory))->create();
        $this->tracerProvider =  new TracerProvider(
            new SimpleSpanProcessor($spanExporter),
        );
        $logsExporter         = (new LogsExporterFactory($transportFactory))->create();
        $meterExporter        = (new MetricExporterFactory($transportFactory))->create();

        // @todo "The exporter MUST be paired with a periodic exporting MetricReader"
        $reader   = new ExportingReader($meterExporter);
        $resource = ResourceInfoFactory::defaultResource();

        $this->meterProvider = MeterProvider::builder()
            ->setResource($resource)
            ->addReader($reader)
            ->setExemplarFilter(new AllExemplarFilter())
            ->build();

        $processor                   = (new LogRecordProcessorFactory())->create($logsExporter, $this->meterProvider);
        $instrumentationScopeFactory = new InstrumentationScopeFactory((new LogRecordLimitsBuilder())->build()->getAttributeFactory());

        $this->loggerProvider = new LoggerProvider($processor, $instrumentationScopeFactory, $resource);
    }

    /** @phpstan-ignore shipmonk.deadMethod */
    public function kernel(Kernel $kernel): void
    {
        Sdk::builder()
            ->setAutoShutdown(true)
            ->setTracerProvider($this->tracerProvider)
            ->setLoggerProvider($this->loggerProvider)
            ->setMeterProvider($this->meterProvider)
            ->setPropagator((new PropagatorFactory())->create())
            ->buildAndRegisterGlobal();

        Loop::addPeriodicTimer(1, async(fn (): bool => $this->tracerProvider->forceFlush()));
        Loop::addPeriodicTimer(1, async(fn (): bool => $this->meterProvider->forceFlush()));
        Loop::addPeriodicTimer(1, async(fn (): bool => $this->loggerProvider->forceFlush(null)));
    }

    /** @phpstan-ignore shipmonk.deadMethod */
    public function shutdown(Shutdown $shutdown): void
    {
        Loop::addTimer(3, async(fn (): bool => $this->tracerProvider->shutdown()));
        Loop::addTimer(3, async(fn (): bool => $this->meterProvider->shutdown()));
        Loop::addTimer(3, async(fn (): bool => $this->loggerProvider->shutdown()));
    }
}
