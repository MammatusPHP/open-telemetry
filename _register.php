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

(async(static function (): void {
    $resource         = ResourceInfoFactory::defaultResource();
    $transportFactory = new OtlpHttpTransportFactory(new Browser());
    $emitMetrics      = Configuration::getBoolean(Variables::OTEL_PHP_INTERNAL_METRICS_ENABLED);

    $meterExporter = (new MetricExporterFactory($transportFactory))->create();

    // @todo "The exporter MUST be paired with a periodic exporting MetricReader"
    $reader   = new ExportingReader($meterExporter);
    $resource = ResourceInfoFactory::defaultResource();

    $meterProvider = MeterProvider::builder()
        ->setResource($resource)
        ->addReader($reader)
        ->setExemplarFilter(new AllExemplarFilter())
        ->build();

    $logsExporter = (new LogsExporterFactory($transportFactory))->create();

    $processor                   = (new LogRecordProcessorFactory())->create($logsExporter, $meterProvider);
    $instrumentationScopeFactory = new InstrumentationScopeFactory((new LogRecordLimitsBuilder())->build()->getAttributeFactory());

    $loggerProvider = new LoggerProvider($processor, $instrumentationScopeFactory, $resource);

    $spanExporter         = (new SpanExporterFactory($transportFactory))->create();
    $tracerProvider =  new TracerProvider(
        new BatchSpanProcessor(
            $spanExporter,
            Clock::getDefault(),
            BatchSpanProcessor::DEFAULT_MAX_QUEUE_SIZE,
            1000,
            BatchSpanProcessor::DEFAULT_EXPORT_TIMEOUT,
            BatchSpanProcessor::DEFAULT_MAX_EXPORT_BATCH_SIZE,
            true,
            $meterProvider
        ),
    );

    Sdk::builder()
        ->setAutoShutdown(true)
        ->setTracerProvider($tracerProvider)
        ->setLoggerProvider($loggerProvider)
        ->setMeterProvider($meterProvider)
        ->setPropagator((new PropagatorFactory())->create())
        ->buildAndRegisterGlobal();
}))();
