<?php

declare(strict_types=1);

namespace Mammatus\Tests\OpenTelemetry;

use Mammatus\OpenTelemetry\Bootstrap;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function defined;
use function dirname;
use function function_exists;

final class BootstrapTest extends TestCase
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
    public function bootstrapPhpEntryPointIsSafeToRequire(): void
    {
        require dirname(__DIR__) . '/src/bootstrap.php';

        self::assertTrue(defined('MAMMATUS_OTEL_FIBERS_SETUP'));
    }
}
