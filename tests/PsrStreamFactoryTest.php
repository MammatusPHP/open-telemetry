<?php

declare(strict_types=1);

namespace Mammatus\Tests\OpenTelemetry;

use Mammatus\OpenTelemetry\PsrStreamFactory;
use PHPUnit\Framework\Attributes\Test;
use React\Filesystem\Factory;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;

use function fclose;
use function fwrite;
use function React\Async\await;
use function stream_socket_pair;

use const STREAM_IPPROTO_IP;
use const STREAM_PF_UNIX;
use const STREAM_SOCK_STREAM;

final class PsrStreamFactoryTest extends AsyncTestCase
{
    #[Test]
    public function createStream(): void
    {
        $factory = new PsrStreamFactory();

        self::assertSame('content', $factory->createStream('content')->getContents());
    }

    #[Test]
    public function createStreamFromFile(): void
    {
        $file = $this->getTmpDir() . 'test.txt';
        await(Factory::create()->file($file)->putContents('hello'));

        $factory = new PsrStreamFactory();

        self::assertSame('hello', $factory->createStreamFromFile($file)->getContents());
    }

    #[Test]
    public function createStreamFromResource(): void
    {
        $sockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        self::assertIsArray($sockets);
        [$read, $write] = $sockets;
        self::assertIsResource($read);
        self::assertIsResource($write);
        /** @phpstan-ignore wyrihaximus.reactphp.blocking.function.fwrite */
        fwrite($write, 'world');
        /** @phpstan-ignore wyrihaximus.reactphp.blocking.function.fclose */
        fclose($write);

        $factory = new PsrStreamFactory();

        self::assertSame('world', $factory->createStreamFromResource($read)->getContents());
    }
}
