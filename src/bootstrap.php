<?php

declare(strict_types=1);

use Mammatus\OpenTelemetry\Fiber\Factory;

if (! defined('MAMMATUS_OTEL_FIBERS_SETUP')) {
    define('MAMMATUS_OTEL_FIBERS_SETUP', true);

    include_once __DIR__ . DIRECTORY_SEPARATOR . 'functions.php';

//    if (! ZendObserverFiber::isEnabled()) {
        Factory::init();
//    }
}
