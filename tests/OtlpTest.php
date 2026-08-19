<?php

declare(strict_types=1);

namespace Mammatus\Tests\OpenTelemetry;

use Mammatus\LifeCycleEvents\Kernel;
use Mammatus\LifeCycleEvents\Shutdown;
use Mammatus\OpenTelemetry\Otlp;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use React\Http\Browser;
use React\Http\Message\Response;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;

use function React\Async\await;
use function React\Promise\resolve;
use function React\Promise\Timer\sleep;

final class OtlpTest extends AsyncTestCase
{
    #[Test]
    public function lifecycle(): void
    {
        $this->expectNotToPerformAssertions();

        $browser = Mockery::mock(Browser::class);
        $browser->shouldReceive('request')->andReturn(resolve(new Response()));

        $otlp = new Otlp($browser);
        $otlp->kernel(new Kernel());
        $otlp->shutdown(new Shutdown());

        await(sleep(3.1));
    }
}
