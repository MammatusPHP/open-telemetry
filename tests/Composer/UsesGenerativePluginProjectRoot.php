<?php

declare(strict_types=1);

namespace Mammatus\Tests\OpenTelemetry\Composer;

use PHPUnit\Framework\Attributes\Before;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;

/** @phpstan-require-extends AsyncTestCase */
trait UsesGenerativePluginProjectRoot
{
    private string $projectRoot;

    #[Before(-10)]
    protected function createGenerativePluginProjectRoot(): void
    {
        $this->projectRoot = GenerativePluginProject::create($this->getTmpDir());
    }
}
