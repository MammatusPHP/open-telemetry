<?php

declare(strict_types=1);

namespace Mammatus\OpenTelemetry;

use Mammatus\LifeCycleEvents\Kernel;
use Mammatus\LifeCycleEvents\Shutdown;
use OpenTelemetry\API\Common\Time\Clock;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Instrumentation\Configurator;
use OpenTelemetry\Context\ScopeInterface;
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
use OpenTelemetry\SDK\Trace\SpanProcessor\BatchSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use OpenTelemetry\SDK\Trace\TracerProviderInterface;
use React\EventLoop\Loop;
use React\EventLoop\TimerInterface;
use React\Http\Browser;
use WyriHaximus\Broadcast\Contracts\Listener;

/**
 * Sync {@see Listener}: {@see \OpenTelemetry\SDK\SdkBuilder::buildAndRegisterGlobal()} only
 * attaches the SDK to the current context scope. Running that inside {@see AsyncListener}'s
 * async() fiber made the providers vanish when the fiber ended, so WithSpan always hit
 * NoopTracerProvider.
 */
final class Otlp implements Listener
{
    private const float PROVIDER_SHUTDOWN_DELAY = 3.0;

    private readonly OtlpHttpTransportFactory $transportFactory;
    private readonly LoggerProviderInterface $loggerProvider;
    private readonly TracerProviderInterface $tracerProvider;
    private readonly MeterProviderInterface $meterProvider;
    private ScopeInterface|null $scope = null;
    /** @var array<TimerInterface> */
    private array $timers = [];

    /** @phpstan-ignore shipmonk.deadMethod */
    public function __construct(Browser $browser)
    {
        $this->transportFactory = new OtlpHttpTransportFactory($browser);
        $spanExporter           = new SpanExporterFactory($this->transportFactory)->create();
        $logsExporter           = new LogsExporterFactory($this->transportFactory)->create();
        $meterExporter          = new MetricExporterFactory($this->transportFactory)->create();

        // @todo "The exporter MUST be paired with a periodic exporting MetricReader"
        $reader   = new ExportingReader($meterExporter);
        $resource = ResourceInfoFactory::defaultResource();

        $this->meterProvider = MeterProvider::builder()
            ->setResource($resource)
            ->addReader($reader)
            ->setExemplarFilter(new AllExemplarFilter())
            ->build();

        $this->tracerProvider        =  new TracerProvider(
            new BatchSpanProcessor($spanExporter, Clock::getDefault(), maxExportBatchSize: 13, meterProvider: $this->meterProvider),
        );
        $processor                   = new LogRecordProcessorFactory()->create($logsExporter, $this->meterProvider);
        $instrumentationScopeFactory = new InstrumentationScopeFactory(new LogRecordLimitsBuilder()->build()->getAttributeFactory());

        $this->loggerProvider = new LoggerProvider($processor, $instrumentationScopeFactory, $resource);
    }

    /** @phpstan-ignore shipmonk.deadMethod */
    public function kernel(Kernel $kernel): void
    {
        $propagator = new PropagatorFactory()->create();

        // Fallback when a fiber context lacks the attached providers.
        /** @phpstan-ignore staticMethod.internal */
        Globals::registerInitializer(fn (Configurator $configurator): Configurator => $configurator
            ->withTracerProvider($this->tracerProvider)
            ->withMeterProvider($this->meterProvider)
            ->withLoggerProvider($this->loggerProvider)
            ->withPropagator($propagator));

        // Manual Shutdown listener owns provider teardown; PHP exit()/auto-shutdown
        // re-entering providers after a React loop hang is a busy-loop footgun.
        // Keep the scope so Context attach is not torn down by GC.
        $this->scope = Sdk::builder()
            ->setAutoShutdown(false)
            ->setTracerProvider($this->tracerProvider)
            ->setLoggerProvider($this->loggerProvider)
            ->setMeterProvider($this->meterProvider)
            ->setPropagator($propagator)
            ->buildAndRegisterGlobal();

        $this->timers[] = Loop::addPeriodicTimer(1, async(fn (): bool => $this->tracerProvider->forceFlush()));
        $this->timers[] = Loop::addPeriodicTimer(1, async(fn (): bool => $this->meterProvider->forceFlush()));
        $this->timers[] = Loop::addPeriodicTimer(1, async(fn (): bool => $this->loggerProvider->forceFlush(null)));
    }

    /** @phpstan-ignore shipmonk.deadMethod */
    public function shutdown(Shutdown $shutdown): void
    {
        foreach ($this->timers as $timer) {
            Loop::cancelTimer($timer);
        }

        $this->timers = [];

        $this->transportFactory->enterShutdownMode();

        Loop::addTimer(self::PROVIDER_SHUTDOWN_DELAY, async(function (): bool {
            $this->scope?->detach();
            $this->scope = null;

            return $this->tracerProvider->shutdown();
        }));
        Loop::addTimer(self::PROVIDER_SHUTDOWN_DELAY, async(fn (): bool => $this->meterProvider->shutdown()));
        Loop::addTimer(self::PROVIDER_SHUTDOWN_DELAY, async(fn (): bool => $this->loggerProvider->shutdown()));
    }
}
