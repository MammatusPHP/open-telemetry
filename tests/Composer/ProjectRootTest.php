<?php

declare(strict_types=1);

namespace Mammatus\Tests\OpenTelemetry\Composer;

use Mammatus\OpenTelemetry\Composer\ProjectRoot;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function dirname;

final class ProjectRootTest extends TestCase
{
    #[Test]
    public function relativePathFromProjectFile(): void
    {
        $absolutePath = dirname(__DIR__, 2) . '/src/Composer/ProjectRoot.php';

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
            $projectRoot . '/src/Composer/ProjectRoot.php',
            ProjectRoot::absolutePath($projectRoot, $relative),
        );
    }

    #[Test]
    public function absolutePathWithoutComposerInstalledJson(): void
    {
        self::assertSame(
            '/foo/bar',
            ProjectRoot::absolutePath('/etc/hosts', 'foo/bar'),
        );
    }
}
