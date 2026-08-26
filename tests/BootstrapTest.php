<?php

declare(strict_types=1);

namespace Mammatus\Tests\OpenTelemetry;

use Mammatus\OpenTelemetry\Bootstrap;
use PHPUnit\Framework\Attributes\Test;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;

use function defined;
use function dirname;
use function function_exists;

final class BootstrapTest extends AsyncTestCase
{
    #[Test]
    public function onceIsIdempotent(): void
    {
        Bootstrap::once();
        Bootstrap::once();

        self::assertTrue(defined('MAMMATUS_OTEL_FIBERS_SETUP'));
        self::assertTrue(function_exists('Mammatus\OpenTelemetry\async'));
    }

    #[Test]
    public function registerPhpEntryPointIsSafeToRequire(): void
    {
        require dirname(__DIR__) . '/src/register.php';

        self::assertTrue(defined('MAMMATUS_OTEL_FIBERS_SETUP'));
    }
}
