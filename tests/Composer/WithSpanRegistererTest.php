<?php

declare(strict_types=1);

namespace Mammatus\Tests\OpenTelemetry\Composer;

use Mammatus\OpenTelemetry\Composer\Item;
use Mammatus\OpenTelemetry\Composer\WithSpanRegisterer;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\Test;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;

use function file_get_contents;
use function file_put_contents;
use function mkdir;
use function substr_count;

final class WithSpanRegistererTest extends AsyncTestCase
{
    use UsesGenerativePluginProjectRoot;

    #[Before(-20)]
    protected function addWithSpanRegistrarTemplate(): void
    {
        GenerativePluginProject::addWithSpanRegistrarTemplate($this->projectRoot);
    }

    #[Test]
    public function compileSkipsNonWithSpanItems(): void
    {
        new WithSpanRegisterer()->compile(
            $this->projectRoot,
            GenerativePluginProject::nonItem(),
            Item::forFileName('src/Target.php'),
        );

        self::assertFileDoesNotExist($this->projectRoot . 'src/WithSpan/Registrar.php');
    }

    #[Test]
    public function compileRemovesExistingRegistrarWhenNothingToRegister(): void
    {
        mkdir($this->projectRoot . 'src/WithSpan', recursive: true);
        $registrarPath = $this->projectRoot . 'src/WithSpan/Registrar.php';
        file_put_contents($registrarPath, '<?php // stale');

        new WithSpanRegisterer()->compile($this->projectRoot);

        self::assertFileDoesNotExist($registrarPath);
    }

    #[Test]
    public function compileGeneratesWithSpanRegistrar(): void
    {
        new WithSpanRegisterer()->compile(
            $this->projectRoot,
            /** @phpstan-ignore argument.type */
            Item::forClass('Acme\\Greeter'),
            /** @phpstan-ignore argument.type */
            Item::forClass('Acme\\Greeter'),
            /** @phpstan-ignore argument.type */
            Item::forClass('Acme\\Other'),
        );

        $contents = file_get_contents($this->projectRoot . 'src/WithSpan/Registrar.php');
        self::assertIsString($contents);
        self::assertStringContainsString('implements AsyncListener', $contents);
        self::assertStringContainsString('function kernel(Kernel $kernel)', $contents);
        self::assertStringContainsString('Hooks::registerClass(\\Acme\\Greeter::class);', $contents);
        self::assertStringContainsString('Hooks::registerClass(\\Acme\\Other::class);', $contents);
        self::assertSame(1, substr_count($contents, 'Acme\\Greeter::class'));
    }
}
