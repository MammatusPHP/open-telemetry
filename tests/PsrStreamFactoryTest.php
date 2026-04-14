<?php

declare(strict_types=1);

namespace Mammatus\Tests\OpenTelemetry;

use Mammatus\OpenTelemetry\PsrStreamFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function fclose;
use function file_put_contents;
use function fwrite;
use function stream_socket_pair;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

use const STREAM_IPPROTO_IP;
use const STREAM_PF_UNIX;
use const STREAM_SOCK_STREAM;

final class PsrStreamFactoryTest extends TestCase
{
    #[Test]
    public function createStream(): void
    {
        $stream = new PsrStreamFactory()->createStream('payload');

        self::assertSame('payload', $stream->getContents());
    }

    #[Test]
    public function createStreamFromFile(): void
    {
        $path = sys_get_temp_dir() . '/mammatus-otel-stream-' . uniqid('', true) . '.txt';
        file_put_contents($path, 'from-file');

        try {
            $stream = new PsrStreamFactory()->createStreamFromFile($path);

            self::assertSame('from-file', $stream->getContents());
        } finally {
            unlink($path);
        }
    }

    #[Test]
    public function createStreamFromResource(): void
    {
        $sockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        self::assertIsArray($sockets);
        $read  = $sockets[0];
        $write = $sockets[1];
        self::assertIsResource($read);
        self::assertIsResource($write);
        fwrite($write, 'from-resource');
        fclose($write);

        try {
            $stream = new PsrStreamFactory()->createStreamFromResource($read);

            self::assertSame('from-resource', $stream->getContents());
        } finally {
            fclose($read);
        }
    }
}
