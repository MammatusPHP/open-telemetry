<?php

declare(strict_types=1);

namespace Mammatus\Tests\OpenTelemetry\Fixtures;

use Exception;
use Http\Discovery\Psr17FactoryDiscovery;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;

/** @phpstan-ignore ergebnis.noExtends */
final class TestNetworkException extends Exception implements NetworkExceptionInterface
{
    public function getRequest(): RequestInterface
    {
        return Psr17FactoryDiscovery::findRequestFactory()->createRequest('POST', 'https://example.com');
    }
}
