<?php

declare(strict_types=1);

namespace Mammatus\Tests\OpenTelemetry\Fiber;

use Mammatus\Tests\OpenTelemetry\OtelFibersEnabled;
use PHPUnit\Framework\Attributes\Test;
use WyriHaximus\TestUtilities\TestCase;

use function array_filter;
use function array_key_last;
use function array_map;
use function array_values;
use function dirname;
use function escapeshellarg;
use function explode;
use function fclose;
use function getenv;
use function proc_close;
use function proc_open;
use function stream_get_contents;
use function trim;

use const PHP_BINARY;
use const PHP_OS_FAMILY;

final class SyncAwaitProcessTest extends TestCase
{
    use OtelFibersEnabled;

    #[Test]
    public function syncAwaitFromCliProcess(): void
    {
        $fixture = dirname(__DIR__) . '/Fixtures/sync-await.php';

        $this->withUserlandFiberTracking(static function () use ($fixture): void {
            // Userland in child: ZendObserverFiber pollutes stdout on Windows (and ZTS).
            $descriptorSpec = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ];

            if (PHP_OS_FAMILY === 'Windows') {
                // putenv() and proc_open env= are unreliable on Windows CI; set in cmd line.
                // Do not wrap in cmd /C "..." — escapeshellarg() quotes break nested parsing.
                $command = 'set OTEL_PHP_FIBERS_ENABLED=false&& '
                    . escapeshellarg(PHP_BINARY)
                    . ' -d display_errors=0 '
                    . escapeshellarg($fixture);
                $process = proc_open($command, $descriptorSpec, $pipes);
            } else {
                /** @var array<string, string> $environment */
                $environment                            = getenv();
                $environment['OTEL_PHP_FIBERS_ENABLED'] = 'false';
                $process                                = proc_open(
                    [PHP_BINARY, '-d', 'display_errors=0', $fixture],
                    $descriptorSpec,
                    $pipes,
                    null,
                    $environment,
                );
            }

            self::assertNotFalse($process);

            fclose($pipes[0]);
            $stdout = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[2]);
            $exitCode = proc_close($process);

            $lines = array_values(array_filter(
                array_map(trim(...), explode("\n", $stdout)),
                static fn (string $line): bool => $line !== '',
            ));

            self::assertSame(0, $exitCode, $stdout . $stderr);
            self::assertNotSame([], $lines);
            $lastIndex = array_key_last($lines);
            self::assertSame('ok', $lines[$lastIndex]);
            self::assertStringNotContainsString('spl_object_id', $stdout);
        });
    }
}
