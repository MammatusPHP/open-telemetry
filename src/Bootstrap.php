<?php

declare(strict_types=1);

namespace Mammatus\OpenTelemetry;

use Mammatus\OpenTelemetry\Fiber\Factory;
use OpenTelemetry\SDK\Registry;

use function define;
use function defined;

use const DIRECTORY_SEPARATOR;

final class Bootstrap
{
    public static function once(): void
    {
        Registry::registerTransportFactory('http', OtlpHttpTransportFactory::class, true);
        Registry::registerTransportFactory('stream', StreamTransportFactory::class, true);

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
