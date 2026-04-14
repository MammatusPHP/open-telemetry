<?php

declare(strict_types=1);

namespace Mammatus\Tests\OpenTelemetry\Composer;

use Mammatus\OpenTelemetry\Composer\ProjectRoot;
use PHPUnit\Framework\Attributes\Test;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;

use function dirname;

use const DIRECTORY_SEPARATOR;

final class ProjectRootTest extends AsyncTestCase
{
    #[Test]
    public function relativePathFromProjectFile(): void
    {
        $absolutePath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Composer' . DIRECTORY_SEPARATOR . 'ProjectRoot.php';

        self::assertSame(
            'src/Composer/ProjectRoot.php',
            ProjectRoot::relativePath($absolutePath),
        );
    }

    #[Test]
    public function absolutePathRoundTrip(): void
    {
        $projectRoot = dirname(__DIR__, 2);
        $relative    = 'src/Composer/ProjectRoot.php';

        self::assertSame(
            $projectRoot . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Composer' . DIRECTORY_SEPARATOR . 'ProjectRoot.php',
            ProjectRoot::absolutePath($projectRoot, $relative),
        );
    }

    #[Test]
    public function absolutePathWithoutComposerInstalledJson(): void
    {
        $result = ProjectRoot::absolutePath($this->getTmpDir(), 'foo/bar');

        self::assertStringEndsWith(
            DIRECTORY_SEPARATOR . 'foo' . DIRECTORY_SEPARATOR . 'bar',
            $result,
        );
    }
}
