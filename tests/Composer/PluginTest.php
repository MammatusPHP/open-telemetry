<?php

declare(strict_types=1);

namespace Mammatus\Tests\OpenTelemetry\Composer;

use Mammatus\OpenTelemetry\Composer\Item;
use Mammatus\OpenTelemetry\Composer\Plugin;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WyriHaximus\Composer\GenerativePluginTooling\Item as ItemContract;
use WyriHaximus\Composer\GenerativePluginTooling\LogStages;

use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function is_dir;
use function mkdir;
use function rmdir;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

final class PluginTest extends TestCase
{
    /** @var non-empty-string */
    private string $projectRoot;

    protected function setUp(): void
    {
        $this->projectRoot = sys_get_temp_dir() . '/mammatus-otel-' . uniqid('', true);
        mkdir($this->projectRoot . '/vendor/composer', recursive: true);
        file_put_contents($this->projectRoot . '/vendor/composer/installed.json', '{}');
        mkdir($this->projectRoot . '/src', recursive: true);
    }

    protected function tearDown(): void
    {
        foreach (
            [
                $this->projectRoot . '/src/Target.php',
                $this->projectRoot . '/src/functions.php',
                $this->projectRoot . '/src/Composer/Plugin.php',
                $this->projectRoot . '/src/Composer',
                $this->projectRoot . '/src',
                $this->projectRoot . '/vendor/composer/installed.json',
                $this->projectRoot . '/vendor/composer',
                $this->projectRoot . '/vendor',
                $this->projectRoot,
            ] as $path
        ) {
            if (! file_exists($path)) {
                continue;
            }

            if (is_dir($path)) {
                rmdir($path);
                continue;
            }

            unlink($path);
        }
    }

    #[Test]
    public function pluginName(): void
    {
        self::assertSame('mammatus/open-telemetry', Plugin::name());
    }

    #[Test]
    public function log(): void
    {
        self::assertSame('Locating files to monkey patch', Plugin::log(LogStages::Init));
        self::assertSame('An error occurred: %s', Plugin::log(LogStages::Error));
        self::assertSame('Found %d file(s) to monkey patch', Plugin::log(LogStages::Collected));
        self::assertSame(
            'Monkey Patched React\Async\async to Mammatus\OpenTelemetry\async in %s second(s)',
            Plugin::log(LogStages::Completion),
        );
    }

    #[Test]
    public function filtersAndCollectors(): void
    {
        $plugin = new Plugin();

        self::assertNotEmpty([...$plugin->filters()]);
        self::assertNotEmpty([...$plugin->collectors()]);
    }

    #[Test]
    public function compileSkipsNonItems(): void
    {
        $plugin = new Plugin();
        $item   = new class implements ItemContract {
            public function jsonSerialize(): mixed
            {
                return [];
            }
        };

        $plugin->compile($this->projectRoot, $item);

        $this->expectNotToPerformAssertions();
    }

    #[Test]
    public function compileSkipsWhenFileCannotBeRead(): void
    {
        new Plugin()->compile($this->projectRoot, new Item('src/does-not-exist.php'));

        $this->expectNotToPerformAssertions();
    }

    #[Test]
    public function compileMonkeyPatchesReactAsyncReferences(): void
    {
        $relativePath = 'src/Target.php';
        $absolutePath = $this->projectRoot . '/' . $relativePath;
        file_put_contents(
            $absolutePath,
            '<?php use function React\Async\async; async(static function (): void {});',
        );

        new Plugin()->compile($this->projectRoot, new Item($relativePath));

        $contents = file_get_contents($absolutePath);
        self::assertIsString($contents);
        self::assertStringContainsString('Mammatus\OpenTelemetry\async', $contents);
        self::assertStringNotContainsString('React\Async\async', $contents);
    }

    #[Test]
    public function compileSkipsFunctionsPhp(): void
    {
        $relativePath = 'src/functions.php';
        $absolutePath = $this->projectRoot . '/' . $relativePath;
        $original     = '<?php use function React\Async\async; async(static function (): void {});';
        file_put_contents($absolutePath, $original);

        new Plugin()->compile($this->projectRoot, new Item($relativePath));

        self::assertSame($original, file_get_contents($absolutePath));
    }

    #[Test]
    public function compileSkipsPluginPhp(): void
    {
        mkdir($this->projectRoot . '/src/Composer', recursive: true);
        $relativePath = 'src/Composer/Plugin.php';
        $absolutePath = $this->projectRoot . '/' . $relativePath;
        $original     = '<?php use function React\Async\async; async(static function (): void {});';
        file_put_contents($absolutePath, $original);

        new Plugin()->compile($this->projectRoot, new Item($relativePath));

        self::assertSame($original, file_get_contents($absolutePath));
    }
}
