<?php

declare(strict_types=1);

namespace Mammatus\Tests\OpenTelemetry;

use BadMethodCallException;
use Mammatus\OpenTelemetry\OtlpHttpTransportFactory;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use React\Http\Browser;
use React\Http\Message\Response;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;

use function React\Async\await;
use function React\Promise\resolve;
use function React\Promise\Timer\sleep;

final class OtlpHttpTransportFactoryTest extends AsyncTestCase
{
    #[Test]
    public function send(): void
    {
        $browser = Mockery::mock(Browser::class);
        $browser->shouldReceive('request')->with(
            'POST',
            'https://example.com/v1/otlp',
            [
                'Host' => ['example.com'],
                'Content-Type' => ['application/json+protobuf'],
            ],
            'abc',
        )->once()->andReturn(resolve(new Response()));

        $ohtf = new OtlpHttpTransportFactory($browser)->create('https://example.com/v1/otlp', 'application/json+protobuf');
        $ohtf->send('abc')->await();
    }

    #[Test]
    public function createWithNoneCompression(): void
    {
        $browser = Mockery::mock(Browser::class);
        $browser->shouldReceive('request')->once()->andReturn(resolve(new Response()));

        $transport = new OtlpHttpTransportFactory($browser)->create(
            'https://example.com/v1/otlp',
            'application/json+protobuf',
            compression: 'none',
        );

        $transport->send('abc')->await();
    }

    #[Test]
    public function enterShutdownModeStopsAcceptingAfterDelay(): void
    {
        $browser = Mockery::mock(Browser::class);
        $browser->shouldReceive('request')->once()->andReturn(resolve(new Response()));

        $factory   = new OtlpHttpTransportFactory($browser, 0.05);
        $transport = $factory->create('https://example.com/v1/otlp', 'application/json+protobuf');

        $factory->enterShutdownMode();
        $factory->enterShutdownMode();

        $transport->send('abc')->await();

        await(sleep(0.06));

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessageIsOrContains('Transport closed');

        $transport->send('abc')->await();
    }
}
