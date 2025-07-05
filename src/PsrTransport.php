<?php

declare(strict_types=1);

namespace Mammatus\OpenTelemetry;

use BadMethodCallException;
use OpenTelemetry\SDK\Common\Export\Http\PsrUtils;
use OpenTelemetry\SDK\Common\Export\TransportInterface;
use OpenTelemetry\SDK\Common\Future\CancellationInterface;
use OpenTelemetry\SDK\Common\Future\CompletedFuture;
use OpenTelemetry\SDK\Common\Future\ErrorFuture;
use OpenTelemetry\SDK\Common\Future\FutureInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use RuntimeException;
use Throwable;

use function assert;
use function explode;
use function in_array;
use function React\Async\await;
use function React\Promise\Timer\sleep;
use function strtolower;
use function trim;

// phpcs:disable
/**
 * @psalm-template CONTENT_TYPE of string
 * @template-implements TransportInterface<CONTENT_TYPE>
 */
final class PsrTransport implements TransportInterface
{
    private bool $closed = false;

    /**
     * @psalm-param CONTENT_TYPE $contentType
     */
    public function __construct(
        private readonly ClientInterface $client,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly string $endpoint,
        private readonly string $contentType,
        private readonly array $headers,
        private readonly array $compression,
        private readonly int $retryDelay,
        private readonly int $maxRetries,
    ) {
    }

    public function contentType(): string
    {
        return $this->contentType;
    }

    /**
     * @psalm-suppress ArgumentTypeCoercion
     * @phpstan-ignore missingType.generics,ergebnis.noParameterWithNullDefaultValue,ergebnis.noParameterWithNullableTypeDeclaration
     */
    public function send(string $payload, CancellationInterface|null $cancellation = null): FutureInterface
    {
        if ($this->closed) {
            return new ErrorFuture(new BadMethodCallException('Transport closed'));
        }

        /** @phpstan-ignore staticMethod.internalClass,argument.type */
        $body    = PsrUtils::encode($payload, $this->compression, $appliedEncodings);
        $request = $this->requestFactory
            ->createRequest('POST', $this->endpoint)
            ->withBody($this->streamFactory->createStream($body))
            ->withHeader('Content-Type', $this->contentType);
        /** @phpstan-ignore if.condNotBoolean */
        if ($appliedEncodings) {
            $request = $request->withHeader('Content-Encoding', $appliedEncodings);
        }

        foreach ($this->headers as $header => $value) {
            /** @phpstan-ignore argument.type */
            $request = $request->withAddedHeader($header, $value);
        }

        for ($retries = 0;; $retries++) {
            $response = null;
            $e        = null;

            try {
                $response = $this->client->sendRequest($request);
                if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                    break;
                }

                if ($response->getStatusCode() >= 400 && $response->getStatusCode() < 500 && ! in_array($response->getStatusCode(), [408, 429], true)) {
                    throw new RuntimeException($response->getReasonPhrase(), $response->getStatusCode());
                }
            } catch (NetworkExceptionInterface $e) {
            } catch (Throwable $e) {
                return new ErrorFuture($e);
            }

            if ($retries >= $this->maxRetries) {
                return new ErrorFuture(new RuntimeException('Export retry limit exceeded', 0, $e));
            }

            /** @phpstan-ignore staticMethod.internalClass */
            $delay = PsrUtils::retryDelay($retries, $this->retryDelay, $response);

            try {
                await(sleep($delay));
            } catch (Throwable $e) {
                return new ErrorFuture(new RuntimeException('Export cancelled', 0, $e));
            }
        }

        /** @phpstan-ignore ergebnis.noIsset */
        assert(isset($response));

        try {
            /** @phpstan-ignore staticMethod.internalClass */
            $body = PsrUtils::decode(
                $response->getBody()->__toString(),
                $this->parseContentEncoding($response),
            );
        } catch (Throwable $e) {
            return new ErrorFuture($e);
        }

        return new CompletedFuture($body);
    }

    /** @return list<string> */
    private function parseContentEncoding(ResponseInterface $response): array
    {
        $encodings = [];
        foreach (explode(',', $response->getHeaderLine('Content-Encoding')) as $encoding) {
            if (($encoding = trim($encoding, " \t")) === '') {
                continue;
            }

            $encodings[] = strtolower($encoding);
        }

        return $encodings;
    }

    /** @phpstan-ignore ergebnis.noParameterWithNullableTypeDeclaration,ergebnis.noParameterWithNullDefaultValue */
    public function shutdown(CancellationInterface|null $cancellation = null): bool
    {
        if ($this->closed) {
            return false;
        }

        $this->closed = true;

        return true;
    }

    /** @phpstan-ignore ergebnis.noParameterWithNullableTypeDeclaration,ergebnis.noParameterWithNullDefaultValue */
    public function forceFlush(CancellationInterface|null $cancellation = null): bool
    {
        return ! $this->closed;
    }
}
