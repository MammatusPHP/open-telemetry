<?php

declare(strict_types=1);

namespace Mammatus\Tests\OpenTelemetry\Composer;

use Mammatus\OpenTelemetry\Composer\Item;
use Mammatus\OpenTelemetry\Composer\MonkeyPatcher;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;

use function dirname;
use function file_get_contents;
use function file_put_contents;
use function is_dir;
use function mkdir;

final class MonkeyPatcherTest extends AsyncTestCase
{
    use UsesGenerativePluginProjectRoot;

    #[Test]
    #[DoesNotPerformAssertions]
    public function compileSkipsNonItems(): void
    {
        new MonkeyPatcher()->compile($this->projectRoot, GenerativePluginProject::nonItem());
    }

    #[Test]
    #[DoesNotPerformAssertions]
    public function compileSkipsWhenFileCannotBeRead(): void
    {
        new MonkeyPatcher()->compile($this->projectRoot, Item::forFileName('src/does-not-exist.php'));
    }

    #[Test]
    public function compileMonkeyPatchesReactAsyncReferences(): void
    {
        $relativePath = 'src/Target.php';
        $absolutePath = $this->projectRoot . $relativePath;
        file_put_contents($absolutePath, GenerativePluginProject::REACT_ASYNC_SOURCE);

        new MonkeyPatcher()->compile($this->projectRoot, Item::forFileName($relativePath));

        $contents = file_get_contents($absolutePath);
        self::assertIsString($contents);
        self::assertStringContainsString('Mammatus\OpenTelemetry\async', $contents);
        self::assertStringNotContainsString('React\Async\async', $contents);
    }

    #[Test]
    #[DataProvider('provideSkippedRelativePaths')]
    public function compileSkipsProtectedRelativePaths(string $relativePath): void
    {
        $absolutePath = $this->projectRoot . $relativePath;
        $parentPath   = dirname($absolutePath);
        if (! is_dir($parentPath)) {
            mkdir($parentPath, recursive: true);
        }

        file_put_contents($absolutePath, GenerativePluginProject::REACT_ASYNC_SOURCE);

        new MonkeyPatcher()->compile($this->projectRoot, Item::forFileName($relativePath));

        self::assertSame(GenerativePluginProject::REACT_ASYNC_SOURCE, file_get_contents($absolutePath));
    }

    /** @return iterable<string, array{string}> */
    public static function provideSkippedRelativePaths(): iterable
    {
        yield 'functions' => ['src/functions.php'];
        yield 'monkeyPatcher' => ['src/Composer/MonkeyPatcher.php'];
        yield 'withSpanRegisterer' => ['src/Composer/WithSpanRegisterer.php'];
    }
}
