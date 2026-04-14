<?php

declare(strict_types=1);

use Mammatus\OpenTelemetry\Fiber\Factory;

use function React\Async\await;
use function React\Promise\resolve;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

set_time_limit(30);

Factory::init();

$result = await(resolve('ok'));

if ($result !== 'ok') {
    fwrite(STDERR, 'unexpected result');
    exit(1);
}

echo $result;
