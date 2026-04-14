<?php

declare(strict_types=1);

namespace Mammatus\Tests\OpenTelemetry;

use Mammatus\OpenTelemetry\StreamTransport;
use Mammatus\OpenTelemetry\StreamTransportFactory;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use React\Http\Browser;
use React\Stream\ThroughStream;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;

final class StreamTransportFactoryTest extends AsyncTestCase
{
    #[Test]
    public function createReturnsStreamTransport(): void
    {
        $browser = Mockery::mock(Browser::class);
        $browser->shouldReceive('requestStreaming')
            ->once()
            ->with('POST', 'https://example.com/v1/otlp', ['X-Custom' => 'value'], Mockery::type(ThroughStream::class));

        $transport = new StreamTransportFactory($browser)->create(
            'https://example.com/v1/otlp',
            'application/x-protobuf',
            ['X-Custom' => 'value'],
        );

        self::assertInstanceOf(StreamTransport::class, $transport);
        self::assertSame('application/x-protobuf', $transport->contentType());
    }
}
