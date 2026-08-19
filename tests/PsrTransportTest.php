<?php

declare(strict_types=1);

namespace Mammatus\Tests\OpenTelemetry;

use BadMethodCallException;
use Exception;
use GuzzleHttp\Psr7\Response;
use Http\Discovery\Psr17FactoryDiscovery;
use Mammatus\OpenTelemetry\PsrStreamFactory;
use Mammatus\OpenTelemetry\PsrTransport;
use Mockery;
use OpenTelemetry\SDK\Common\Export\TransportFactoryInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use React\EventLoop\Loop;
use RuntimeException;
use Throwable;
use UnexpectedValueException;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;

use function gzencode;
use function React\Async\async;
use function React\Async\await;

final class PsrTransportTest extends AsyncTestCase
{
    #[Test]
    public function contentType(): void
    {
        $transport = $this->createTransport(Mockery::mock(ClientInterface::class));

        self::assertSame('application/json+protobuf', $transport->contentType());
    }

    #[Test]
    public function sendSuccess(): void
    {
        $client = Mockery::mock(ClientInterface::class);
        $client->shouldReceive('sendRequest')->once()->andReturn(new Response(200, [], 'ok'));

        $transport = $this->createTransport($client);

        self::assertSame('ok', $transport->send('payload')->await());
    }

    #[Test]
    public function sendWithCompressionAndHeaders(): void
    {
        $client = Mockery::mock(ClientInterface::class);
        $client->shouldReceive('sendRequest')->once()->with(Mockery::on(static fn (RequestInterface $request): bool => $request->getHeaderLine('Content-Encoding') === 'gzip'
            && $request->getHeaderLine('X-Custom') === 'value'))->andReturn(new Response(200, [], ''));

        $transport = $this->createTransport(
            $client,
            headers: ['X-Custom' => 'value'],
            compression: [TransportFactoryInterface::COMPRESSION_GZIP],
        );

        $transport->send('payload')->await();
    }

    #[Test]
    public function sendWithContentEncodingResponse(): void
    {
        $client  = Mockery::mock(ClientInterface::class);
        $encoded = gzencode('decoded');
        self::assertIsString($encoded);
        $client->shouldReceive('sendRequest')->once()->andReturn(new Response(200, ['Content-Encoding' => [' GZIP ']], $encoded));

        $transport = $this->createTransport($client);

        self::assertSame('decoded', $transport->send('payload')->await());
    }

    #[Test]
    public function sendWhenClosed(): void
    {
        $transport = $this->createTransport(Mockery::mock(ClientInterface::class));
        $transport->shutdown();

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessageIsOrContains('Transport closed');

        $transport->send('payload')->await();
    }

    #[Test]
    public function sendClientError(): void
    {
        $client = Mockery::mock(ClientInterface::class);
        $client->shouldReceive('sendRequest')->once()->andReturn(new Response(404, [], ''));

        $transport = $this->createTransport($client);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('Not Found');

        $transport->send('payload')->await();
    }

    #[Test]
    public function sendClientThrowable(): void
    {
        $client = Mockery::mock(ClientInterface::class);
        $client->shouldReceive('sendRequest')->once()->andThrow(new Exception('boom'));

        $transport = $this->createTransport($client);

        $this->expectException(Throwable::class);
        $this->expectExceptionMessageIsOrContains('boom');

        $transport->send('payload')->await();
    }

    #[Test]
    public function sendDecodeFailure(): void
    {
        $client = Mockery::mock(ClientInterface::class);
        $client->shouldReceive('sendRequest')->once()->andReturn(new Response(200, ['Content-Encoding' => ['not-a-real-encoding']], 'body'));

        $transport = $this->createTransport($client);

        $this->expectException(UnexpectedValueException::class);

        $transport->send('payload')->await();
    }

    #[Test]
    public function sendRetryThenSuccess(): void
    {
        $client = Mockery::mock(ClientInterface::class);
        $client->shouldReceive('sendRequest')->twice()->andReturn(
            new Response(503, [], ''),
            new Response(200, [], 'done'),
        );

        $transport = $this->createTransport($client, maxRetries: 1);

        self::assertSame('done', $transport->send('payload')->await());
    }

    #[Test]
    public function sendRetryLimitExceeded(): void
    {
        $client = Mockery::mock(ClientInterface::class);
        $client->shouldReceive('sendRequest')->times(2)->andThrow(new TestNetworkException());

        $transport = $this->createTransport($client, retryDelay: 1, maxRetries: 1);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('Export retry limit exceeded');

        $transport->send('payload')->await();
    }

    #[Test]
    public function sendCancelledDuringRetry(): void
    {
        $client = Mockery::mock(ClientInterface::class);
        $client->shouldReceive('sendRequest')->andReturn(new Response(503, [], ''));

        $transport = $this->createTransport($client, retryDelay: 100, maxRetries: 3);

        $promise = async(static fn (): mixed => $transport->send('payload')->await())();

        Loop::addTimer(0.01, static function () use ($promise): void {
            $promise->cancel();
        });

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('Export cancelled');

        await($promise);
    }

    #[Test]
    #[DataProvider('provideShutdownAndForceFlush')]
    public function shutdownAndForceFlush(bool $shutdownFirst, bool $shutdownTwice, bool $expectedForceFlush): void
    {
        $transport = $this->createTransport(Mockery::mock(ClientInterface::class));

        if ($shutdownFirst) {
            self::assertTrue($transport->shutdown());
            self::assertFalse($transport->shutdown());
        }

        if ($shutdownTwice) {
            $transport->shutdown();
            self::assertFalse($transport->shutdown());
        }

        self::assertSame($expectedForceFlush, $transport->forceFlush());
    }

    /** @return iterable<string, array{bool, bool, bool}> */
    public static function provideShutdownAndForceFlush(): iterable
    {
        yield 'open transport' => [false, false, true];
        yield 'shut down once' => [true, false, false];
        yield 'shut down twice' => [false, true, false];
    }

    /**
     * @param array<string, string> $headers
     * @param list<string>          $compression
     *
     * @phpstan-ignore missingType.generics
     */
    private function createTransport(
        ClientInterface $client,
        array $headers = [],
        array $compression = [],
        int $retryDelay = 100,
        int $maxRetries = 3,
    ): PsrTransport {
        return new PsrTransport(
            $client,
            Psr17FactoryDiscovery::findRequestFactory(),
            new PsrStreamFactory(),
            'https://example.com/v1/otlp',
            'application/json+protobuf',
            $headers,
            $compression,
            $retryDelay,
            $maxRetries,
        );
    }
}
