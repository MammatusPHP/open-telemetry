<?php

declare(strict_types=1);

namespace Mammatus\Tests\OpenTelemetry\Composer;

use Mammatus\OpenTelemetry\Composer\Item;
use Mammatus\OpenTelemetry\Composer\MonkeyPatcher;
use Mammatus\OpenTelemetry\Composer\WithSpanItem;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use stdClass;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;

use function file_get_contents;
use function file_put_contents;
use function mkdir;

final class MonkeyPatcherTest extends AsyncTestCase
{
    use UsesGenerativePluginProjectRoot;

    #[Test]
    #[DoesNotPerformAssertions]
    public function compileSkipsWhenNoItems(): void
    {
        new MonkeyPatcher()->compile($this->projectRoot);
    }

    #[Test]
    #[DoesNotPerformAssertions]
    public function compileSkipsNonItems(): void
    {
        new MonkeyPatcher()->compile($this->projectRoot, new WithSpanItem(stdClass::class));
    }

    #[Test]
    #[DoesNotPerformAssertions]
    public function compileSkipsWhenFileCannotBeRead(): void
    {
        new MonkeyPatcher()->compile(
            $this->projectRoot,
            new Item('/nonexistent/path/functions.php', false),
        );
    }

    #[Test]
    public function compileSkipsAlreadyPatched(): void
    {
        mkdir($this->projectRoot . 'vendor/react/async/src', recursive: true);
        file_put_contents(
            $this->projectRoot . 'vendor/react/async/src/functions.php',
            '<?php namespace React\Async; function async_original(callable $function): callable { return $function; }',
        );

        new MonkeyPatcher()->compile(
            $this->projectRoot,
            new Item($this->projectRoot . 'vendor/react/async/src/functions.php', true),
        );

        $contents = file_get_contents($this->projectRoot . 'vendor/react/async/src/functions.php');
        self::assertIsString($contents);
        self::assertStringNotContainsString('\\Mammatus\\OpenTelemetry\\async', $contents);
    }

    #[Test]
    public function compileMonkeyPatchesReactAsyncFunctionsFile(): void
    {
        mkdir($this->projectRoot . 'vendor/react/async/src', recursive: true);
        file_put_contents(
            $this->projectRoot . 'vendor/react/async/src/functions.php',
            '<?php namespace React\Async; function async(callable $function): callable { return $function; }',
        );

        new MonkeyPatcher()->compile(
            $this->projectRoot,
            new Item($this->projectRoot . 'vendor/react/async/src/functions.php', false),
        );

        $contents = file_get_contents($this->projectRoot . 'vendor/react/async/src/functions.php');
        self::assertIsString($contents);
        self::assertStringContainsString('function async_original(callable $function): callable', $contents);
        self::assertStringContainsString('\\Mammatus\\OpenTelemetry\\async($function)', $contents);
    }
}
