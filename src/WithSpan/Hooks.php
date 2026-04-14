<?php

declare(strict_types=1);

namespace Mammatus\OpenTelemetry\WithSpan;

use OpenTelemetry\API\Instrumentation\SpanAttribute;
use OpenTelemetry\API\Instrumentation\WithSpan;
use OpenTelemetry\API\Instrumentation\WithSpanHandler;
use ReflectionClass;
use ReflectionMethod;
use Throwable;

use function array_key_exists;
use function class_exists;
use function extension_loaded;
use function function_exists;
use function get_debug_type;
use function get_object_vars;
use function is_object;
use function is_scalar;
use function method_exists;
use function OpenTelemetry\Instrumentation\hook;
use function sprintf;

/**
 * Registers {@see WithSpanHandler} via ext-opentelemetry {@see hook()} for methods
 * annotated with {@see WithSpan}, without using attr_hooks (avoids zend_mm_heap
 * corruption in the extension's attribute observer under React fibers).
 *
 * @see https://github.com/open-telemetry/opentelemetry-php-instrumentation/pull/313
 * @see ../../etc/adr/001-fiber-context-and-withspan.md
 */
final class Hooks
{
    /** @var array<string, true> */
    private static array $registered = [];

    /**
     * @param class-string $class
     *
     * @phpstan-ignore shipmonk.deadMethod
     */
    public static function registerClass(string $class): void
    {
        if (! self::hooksAvailable() || ! class_exists($class)) {
            return;
        }

        foreach (new ReflectionClass($class)->getMethods() as $method) {
            if ($method->getDeclaringClass()->getName() !== $class) {
                continue;
            }

            if ($method->getAttributes(WithSpan::class) === []) {
                continue;
            }

            self::registerMethod($class, $method);
        }
    }

    /** @param class-string $class */
    private static function registerMethod(string $class, ReflectionMethod $method): void
    {
        $methodName = $method->getName();
        $key        = $class . '::' . $methodName;
        if (array_key_exists($key, self::$registered)) {
            return;
        }

        self::$registered[$key] = true;

        $spanAttributeParams = [];
        foreach ($method->getParameters() as $index => $parameter) {
            if ($parameter->getAttributes(SpanAttribute::class) === []) {
                continue;
            }

            $spanAttributeParams[$index] = $parameter->getName();
        }

        hook(
            $class,
            $methodName,
            /** @phpstan-ignore ergebnis.noParameterWithNullableTypeDeclaration,ergebnis.noParameterWithNullableTypeDeclaration */
            static function (mixed $object, array $params, string $className, string $function, string|null $filename, int|null $lineno) use ($spanAttributeParams): void {
                $attributes = [];
                foreach ($spanAttributeParams as $index => $name) {
                    foreach (self::flattenAttribute($name, $params[$index] ?? null) as $key => $value) {
                        $attributes[$key] = $value;
                    }
                }

                WithSpanHandler::pre($object, $params, $className, $function, $filename, $lineno, [], $attributes);
            },
            static function (mixed $object, array $params, mixed $returnValue, mixed $exception): void {
                // Fiber teardown passes GracefulExit/UnwindExit (not Throwable); do not typehint
                // ?Throwable or the post hook is skipped and spans never export.
                WithSpanHandler::post(
                    $object,
                    $params,
                    $returnValue,
                    $exception instanceof Throwable ? $exception : null,
                );
            },
        );
    }

    /** @return array<string, scalar|null> */
    private static function flattenAttribute(string $name, mixed $value): array
    {
        if (is_scalar($value) || $value === null) {
            return [$name => $value];
        }

        if (is_object($value)) {
            $vars = get_object_vars($value);
            if (
                array_key_exists('type', $vars)
                && array_key_exists('file', $vars)
                && (is_scalar($vars['type']) || $vars['type'] === null)
                && (is_scalar($vars['file']) || $vars['file'] === null)
            ) {
                return [
                    $name . '.type' => $vars['type'],
                    $name . '.file' => $vars['file'],
                ];
            }

            if (method_exists($value, '__toString')) {
                return [$name => (string) $value];
            }

            return [$name => $value::class];
        }

        return [$name => sprintf('[%s]', get_debug_type($value))];
    }

    private static function hooksAvailable(): bool
    {
        // @codeCoverageIgnoreStart
        return extension_loaded('opentelemetry') && function_exists('OpenTelemetry\Instrumentation\hook');
        // @codeCoverageIgnoreEnd
    }
}
