<?php

declare(strict_types=1);

namespace Mammatus\Tests\OpenTelemetry\Composer;

use WyriHaximus\Composer\GenerativePluginTooling\Item as ItemContract;

use function dirname;
use function file_get_contents;
use function file_put_contents;
use function mkdir;

final class GenerativePluginProject
{
    public const string REACT_ASYNC_SOURCE = '<?php use function React\Async\async; async(static function (): void {});';

    public static function create(string $tmpDir): string
    {
        mkdir($tmpDir . 'vendor/composer', recursive: true);
        file_put_contents($tmpDir . 'vendor/composer/installed.json', '{}');
        mkdir($tmpDir . 'src', recursive: true);

        return $tmpDir;
    }

    public static function addWithSpanRegistrarTemplate(string $projectRoot): void
    {
        mkdir($projectRoot . 'etc/generated_templates', recursive: true);
        file_put_contents(
            $projectRoot . 'etc/generated_templates/WithSpanRegistrar.php.twig',
            file_get_contents(dirname(__DIR__, 2) . '/etc/generated_templates/WithSpanRegistrar.php.twig'),
        );
    }

    public static function nonItem(): ItemContract
    {
        return new class implements ItemContract {
            public function jsonSerialize(): mixed
            {
                return [];
            }
        };
    }
}
