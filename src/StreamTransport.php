<?php

declare(strict_types=1);

namespace Mammatus\OpenTelemetry;

use OpenTelemetry\SDK\Common\Export\TransportInterface;
use OpenTelemetry\SDK\Common\Future\CancellationInterface;
use OpenTelemetry\SDK\Common\Future\CompletedFuture;
use OpenTelemetry\SDK\Common\Future\FutureInterface;
use React\Stream\ThroughStream;

// phpcs:disable
/**
 * @psalm-template CONTENT_TYPE of string
 * @template-implements TransportInterface<CONTENT_TYPE>
 */
final readonly class StreamTransport implements TransportInterface
{
    /**
     * @psalm-param CONTENT_TYPE $contentType
     */
    public function __construct(
        private ThroughStream           $stream,
        private string                  $contentType,
    ) {
    }

    public function contentType(): string
    {
        return $this->contentType;
    }

    /**
     * @phpstan-ignore missingType.generics,ergebnis.noParameterWithNullDefaultValue,ergebnis.noParameterWithNullableTypeDeclaration
     */
    public function send(string $payload, CancellationInterface|null $cancellation = null): FutureInterface
    {
        $this->stream->write($payload);

        return new CompletedFuture(null);
    }

    /** @phpstan-ignore ergebnis.noParameterWithNullableTypeDeclaration,ergebnis.noParameterWithNullDefaultValue */
    public function shutdown(CancellationInterface|null $cancellation = null): bool
    {
        return true;
    }

    /** @phpstan-ignore ergebnis.noParameterWithNullableTypeDeclaration,ergebnis.noParameterWithNullDefaultValue */
    public function forceFlush(CancellationInterface|null $cancellation = null): bool
    {
        return true;
    }
}
