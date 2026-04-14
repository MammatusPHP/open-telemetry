<?php

declare(strict_types=1);

namespace Mammatus\OpenTelemetry;

use Mammatus\OpenTelemetry\Fiber\Factory;

use function define;
use function defined;

use const DIRECTORY_SEPARATOR;

final class Bootstrap
{
    public static function once(): void
    {
        if (defined('MAMMATUS_OTEL_FIBERS_SETUP')) {
            return;
        }

        // @codeCoverageIgnoreStart
        define('MAMMATUS_OTEL_FIBERS_SETUP', true);

        include_once __DIR__ . DIRECTORY_SEPARATOR . 'functions.php';

        Factory::init();
        // @codeCoverageIgnoreEnd
    }
}
