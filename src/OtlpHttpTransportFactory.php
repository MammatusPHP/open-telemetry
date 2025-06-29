<?php

declare(strict_types=1);

namespace Mammatus\OpenTelemetry;

use OpenTelemetry\SDK\Common\Export\Http\PsrTransportFactory;
use OpenTelemetry\SDK\Common\Export\TransportFactoryInterface;
use OpenTelemetry\SDK\Common\Export\TransportInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use React\Http\Browser;

use function React\Async\await;

// phpcs:disable
final readonly class OtlpHttpTransportFactory implements TransportFactoryInterface
{
    private const string DEFAULT_COMPRESSION = 'none';

    public function __construct(
        private Browser $browser,
    )
    {

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
        if ($compression === self::DEFAULT_COMPRESSION) {
            $compression = null;
        }

        return (new PsrTransportFactory(new readonly class ($this->browser) implements ClientInterface
        {
            public function __construct(private Browser $browser)
            {
            }

            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                return await($this->browser->request($request->getMethod(), (string) $request->getUri(), $request->getHeaders(), (string) $request->getBody()));
            }
        }))
            ->create($endpoint, $contentType, $headers, $compression, $timeout, $retryDelay, $maxRetries, $cacert, $cert, $key);
    }
}
