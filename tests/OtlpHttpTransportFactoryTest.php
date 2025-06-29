<?php

declare(strict_types=1);

namespace Mammatus\Tests\OpenTelemetry;

use Mammatus\OpenTelemetry\OtlpHttpTransportFactory;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use React\Http\Browser;
use React\Http\Message\Response;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;

use function React\Promise\resolve;

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

        $ohtf = (new OtlpHttpTransportFactory($browser))->create('https://example.com/v1/otlp', 'application/json+protobuf');
        $ohtf->send('abc')->await();
    }
}
