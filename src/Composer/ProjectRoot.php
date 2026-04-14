<?php

declare(strict_types=1);

namespace Mammatus\OpenTelemetry\Composer;

use function dirname;
use function file_exists;
use function is_file;
use function rtrim;
use function str_replace;
use function strlen;
use function substr;

use const DIRECTORY_SEPARATOR;

final class ProjectRoot
{
    public static function relativePath(string $absolutePath): string
    {
        $absolutePath = self::normalizePath($absolutePath);
        $projectRoot  = self::locate($absolutePath);

        return str_replace(
            DIRECTORY_SEPARATOR,
            '/',
            substr($absolutePath, strlen($projectRoot) + 1),
        );
    }

    public static function absolutePath(string $hintPath, string $relativePath): string
    {
        return rtrim(self::locate($hintPath), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    }

    private static function locate(string $path): string
    {
        /** @phpstan-ignore wyrihaximus.reactphp.blocking.function.isFile */
        $dir = is_file($path) ? dirname($path) : self::normalizePath($path);
        while (true) {
            /** @phpstan-ignore wyrihaximus.reactphp.blocking.function.fileExists */
            if (file_exists($dir . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'composer' . DIRECTORY_SEPARATOR . 'installed.json')) {
                return $dir;
            }

            $parent = dirname($dir);
            if ($parent === $dir) {
                return $dir;
            }

            $dir = $parent;
        }
    }

    private static function normalizePath(string $path): string
    {
        return str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path);
    }
}
