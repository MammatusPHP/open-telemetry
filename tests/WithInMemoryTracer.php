<?php

declare(strict_types=1);

namespace Mammatus\Tests\OpenTelemetry;

use OpenTelemetry\API\Globals;
use OpenTelemetry\Context\Context;
use OpenTelemetry\Context\ContextStorageScopeInterface;
use OpenTelemetry\SDK\Sdk;
use OpenTelemetry\SDK\Trace\SpanExporter\InMemoryExporter;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;

use function extension_loaded;

trait WithInMemoryTracer
{
    /** @param callable(InMemoryExporter, TracerProvider): void $callback */
    private function withTracer(callable $callback): void
    {
        if (! extension_loaded('opentelemetry')) {
            self::markTestSkipped('ext-opentelemetry required');
        }

        $this->detachAllContextScopes();
        Globals::reset();

        $exporter       = new InMemoryExporter();
        $tracerProvider = new TracerProvider([new SimpleSpanProcessor($exporter)]);
        $scope          = Sdk::builder()
            ->setTracerProvider($tracerProvider)
            ->setAutoShutdown(true)
            ->buildAndRegisterGlobal();

        try {
            $callback($exporter, $tracerProvider);
        } finally {
            $scope->detach();
            $this->detachAllContextScopes();
            Globals::reset();
        }
    }

    private function detachAllContextScopes(): void
    {
        while (($scope = Context::storage()->scope()) instanceof ContextStorageScopeInterface) {
            $scope->detach();
        }
    }
}
