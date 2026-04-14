<?php

declare(strict_types=1);

namespace Mammatus\Tests\OpenTelemetry;

use Mammatus\OpenTelemetry\StreamTransport;
use PHPUnit\Framework\Attributes\Test;
use React\Stream\ThroughStream;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;

final class StreamTransportTest extends AsyncTestCase
{
    #[Test]
    public function contentType(): void
    {
        $transport = new StreamTransport(new ThroughStream(), 'application/x-protobuf');

        self::assertSame('application/x-protobuf', $transport->contentType());
    }

    #[Test]
    public function sendWritesPayloadToStream(): void
    {
        $stream  = new ThroughStream();
        $written = null;
        $stream->on('data', static function (string $chunk) use (&$written): void {
            $written = $chunk;
        });

        $transport = new StreamTransport($stream, 'application/x-protobuf');

        self::assertNull($transport->send('payload')->await());
        self::assertSame('payload', $written);
    }

    #[Test]
    public function shutdownAndForceFlush(): void
    {
        $transport = new StreamTransport(new ThroughStream(), 'application/x-protobuf');

        self::assertTrue($transport->shutdown());
        self::assertTrue($transport->forceFlush());
    }
}
