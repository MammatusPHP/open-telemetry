<?php

declare(strict_types=1);

namespace Mammatus\OpenTelemetry;

use OpenTelemetry\SDK\Common\Export\TransportFactoryInterface;
use OpenTelemetry\SDK\Common\Export\TransportInterface;
use React\Http\Browser;
use React\Stream\ThroughStream;

// phpcs:disable
final readonly class StreamTransportFactory implements TransportFactoryInterface
{
    /** @phpstan-ignore shipmonk.deadMethod */
    public function __construct(
        private Browser|null $browser = null,
    ) {
    }

    public function create(
        string $endpoint,
        string $contentType,
        array $headers = [],
        $compression = null,
        float $timeout = 10.,
        int $retryDelay = 100,
        int $maxRetries = 3,
        string|null $cacert = null,
        string|null $cert = null,
        string|null $key = null,
    ): TransportInterface {
        $stream = new ThroughStream();

        ($this->browser ?? new Browser())->requestStreaming('POST', $endpoint, $headers, $stream);

        return new StreamTransport($stream, $contentType);
    }
}
