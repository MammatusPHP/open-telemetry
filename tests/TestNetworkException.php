<?php

declare(strict_types=1);

namespace Mammatus\Tests\OpenTelemetry;

use Http\Discovery\Psr17FactoryDiscovery;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;
use RuntimeException;

/** @phpstan-ignore ergebnis.noExtends */
final class TestNetworkException extends RuntimeException implements NetworkExceptionInterface
{
    public function getRequest(): RequestInterface
    {
        return Psr17FactoryDiscovery::findRequestFactory()->createRequest('POST', 'https://example.com');
    }
}
