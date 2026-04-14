<?php

declare(strict_types=1);

namespace Mammatus\Tests\OpenTelemetry;

use BadMethodCallException;
use Closure;
use Http\Discovery\Psr17FactoryDiscovery;
use Mammatus\OpenTelemetry\PsrStreamFactory;
use Mammatus\OpenTelemetry\PsrTransport;
use Mammatus\Tests\OpenTelemetry\Fixtures\TestNetworkException;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Throwable;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;

use function assert;

final class PsrTransportTest extends AsyncTestCase
{
    #[Test]
    public function contentType(): void
    {
        $transport = $this->transport(static fn (): ResponseInterface => self::response(200));

        self::assertSame('application/json', $transport->contentType());
    }

    #[Test]
    public function sendReturnsCompletedFutureOnSuccess(): void
    {
        $transport = $this->transport(static fn (): ResponseInterface => self::response(200, 'ok'));

        self::assertSame('ok', $transport->send('payload')->await());
    }

    #[Test]
    public function sendFailsWhenTransportIsClosed(): void
    {
        $transport = $this->transport(static fn (): ResponseInterface => self::response(200));
        $transport->shutdown();

        $this->expectException(BadMethodCallException::class);
        $transport->send('payload')->await();
    }

    #[Test]
    public function sendFailsOnClientError(): void
    {
        $transport = $this->transport(static fn (): ResponseInterface => self::response(404, 'missing'));

        $this->expectException(RuntimeException::class);
        $transport->send('payload')->await();
    }

    #[Test]
    public function sendRetriesAfterServerError(): void
    {
        $attempts  = 0;
        $transport = $this->transport(
            static function () use (&$attempts): ResponseInterface {
                $attempts++;

                if ($attempts === 1) {
                    return self::response(500, 'fail');
                }

                return self::response(200, 'ok');
            },
            retryDelay: 0,
            maxRetries: 1,
        );

        self::assertSame('ok', $transport->send('payload')->await());
        self::assertSame(2, $attempts);
    }

    #[Test]
    public function sendRetriesAfterTooManyRequests(): void
    {
        $attempts  = 0;
        $transport = $this->transport(
            static function () use (&$attempts): ResponseInterface {
                $attempts++;

                if ($attempts === 1) {
                    return self::response(429, 'slow down');
                }

                return self::response(200, 'ok');
            },
            retryDelay: 0,
            maxRetries: 1,
        );

        self::assertSame('ok', $transport->send('payload')->await());
        self::assertSame(2, $attempts);
    }

    #[Test]
    public function sendFailsWhenRetryLimitExceeded(): void
    {
        $transport = $this->transport(
            static fn (): ResponseInterface => self::response(500, 'fail'),
            retryDelay: 0,
            maxRetries: 0,
        );

        $this->expectException(RuntimeException::class);
        $transport->send('payload')->await();
    }

    #[Test]
    public function sendFailsOnNetworkExceptionAfterRetries(): void
    {
        $transport = $this->transport(
            static function (): ResponseInterface {
                throw new TestNetworkException();
            },
            retryDelay: 0,
            maxRetries: 0,
        );

        $this->expectException(RuntimeException::class);
        $transport->send('payload')->await();
    }

    #[Test]
    public function sendFailsOnUnexpectedThrowable(): void
    {
        $transport = $this->transport(
            static function (): ResponseInterface {
                throw new RuntimeException('boom');
            },
        );

        $this->expectException(RuntimeException::class);
        $transport->send('payload')->await();
    }

    #[Test]
    public function sendAppliesCustomHeadersAndCompression(): void
    {
        $transport = $this->transport(
            static fn (): ResponseInterface => self::response(200, 'ok'),
            headers: ['X-Custom' => 'value'],
            compression: ['gzip'],
        );

        self::assertSame('ok', $transport->send('payload')->await());
    }

    #[Test]
    public function sendIgnoresEmptyContentEncodings(): void
    {
        $transport = $this->transport(
            static fn (): ResponseInterface => self::response(200, 'plain', ['Content-Encoding' => ' , , ']),
        );

        self::assertSame('plain', $transport->send('payload')->await());
    }

    #[Test]
    public function sendFailsWhenResponseBodyCannotBeDecoded(): void
    {
        $transport = $this->transport(
            static fn (): ResponseInterface => self::response(200, 'not-valid', ['Content-Encoding' => 'gzip']),
        );

        $this->expectException(Throwable::class);
        $transport->send('payload')->await();
    }

    #[Test]
    public function shutdownAndForceFlush(): void
    {
        $transport = $this->transport(static fn (): ResponseInterface => self::response(200));

        self::assertTrue($transport->forceFlush());
        self::assertTrue($transport->shutdown());
        self::assertFalse($transport->shutdown());
        self::assertFalse($transport->forceFlush());
    }

    /** @param array<string, string> $headers */
    private static function response(int $status, string $body = '', array $headers = []): ResponseInterface
    {
        $responseFactory = Psr17FactoryDiscovery::findResponseFactory();
        $streamFactory   = Psr17FactoryDiscovery::findStreamFactory();
        $response        = $responseFactory->createResponse($status)->withBody($streamFactory->createStream($body));

        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        return $response;
    }

    /**
     * @param callable(): ResponseInterface $responder
     * @param array<string, string>         $headers
     * @param list<string>                  $compression
     *
     * @phpstan-ignore missingType.generics
     */
    private function transport(
        callable $responder,
        array $headers = [],
        array $compression = [],
        int $retryDelay = 100,
        int $maxRetries = 3,
    ): PsrTransport {
        $responder = Closure::fromCallable($responder);
        $client    = new readonly class ($responder) implements ClientInterface {
            public function __construct(private Closure $responder)
            {
            }

            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                $response = ($this->responder)();
                assert($response instanceof ResponseInterface);

                return $response;
            }
        };

        return new PsrTransport(
            $client,
            Psr17FactoryDiscovery::findRequestFactory(),
            new PsrStreamFactory(),
            'https://example.com/v1/otlp',
            'application/json',
            $headers,
            $compression,
            $retryDelay,
            $maxRetries,
        );
    }
}
