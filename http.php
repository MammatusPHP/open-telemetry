<?php

require 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

for ($i = 0; $i < 10; ++$i) {
    Mammatus\OpenTelemetry\async(function (): int {
        $browser = new React\Http\Browser()->withRejectErrorResponse(false);
        $urls = [
                'https://example.com/alice',
                'https://example.com/bob'
        ];

        $promises = [];
        foreach ($urls as $url) {
            $promises[] = $browser->get($url);
        }

        try {
            $responses = React\Async\await(React\Promise\all($promises));
        } catch (Exception $e) {
            foreach ($promises as $promise) {
                $promise->cancel();
            }
            throw $e;
        }

        $bytes = 0;
        foreach ($responses as $response) {
            assert($response instanceof Psr\Http\Message\ResponseInterface);
            $bytes += $response->getBody()->getSize();
        }
        return $bytes;
    })()->then(function (int $bytes) {
        echo 'Total size: ' . $bytes . PHP_EOL;
    }, function (Exception $e) {
        echo 'Error: ' . $e->getMessage() . PHP_EOL;
    });
}
