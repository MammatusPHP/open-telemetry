<?php

declare(strict_types=1);

namespace Mammatus\Tests\OpenTelemetry\WithSpan\Fixture;

use OpenTelemetry\API\Instrumentation\SpanAttribute;
use OpenTelemetry\API\Instrumentation\WithSpan;

/** @phpstan-ignore ergebnis.final */
class Greeter
{
    #[WithSpan]
    public function hello(
        #[SpanAttribute]
        string $name,
    ): string {
        return 'hello-' . $name;
    }

    #[WithSpan]
    public function withPayload(
        #[SpanAttribute]
        Payload $payload,
    ): string {
        return $payload->type . '/' . $payload->file;
    }

    #[WithSpan]
    public function withNamed(
        #[SpanAttribute]
        Named $named,
    ): string {
        return (string) $named;
    }

    #[WithSpan]
    public function withPlain(
        #[SpanAttribute]
        Plain $plain,
    ): string {
        return $plain::class;
    }

    /** @param list<string> $items */
    #[WithSpan]
    public function withList(
        #[SpanAttribute]
        array $items,
    ): string {
        return 'list';
    }

    #[WithSpan]
    public function mixedParams(
        string $ignored,
        #[SpanAttribute]
        string $name,
    ): string {
        return $ignored . '-' . $name;
    }

    public function withoutSpan(string $name): string
    {
        return $name;
    }
}
