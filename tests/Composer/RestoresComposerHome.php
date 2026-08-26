<?php

declare(strict_types=1);

namespace Mammatus\Tests\OpenTelemetry\Composer;

use PHPUnit\Framework\Attributes\After;

use function getenv;
use function putenv;

trait RestoresComposerHome
{
    private string|null $previousComposerHome = null;

    protected function swapComposerHome(string $home): void
    {
        $this->previousComposerHome = getenv('COMPOSER_HOME') !== false ? (string) getenv('COMPOSER_HOME') : '';
        putenv('COMPOSER_HOME=' . $home);
    }

    #[After]
    protected function restoreComposerHome(): void
    {
        if ($this->previousComposerHome === null) {
            return;
        }

        putenv('COMPOSER_HOME=' . $this->previousComposerHome);
    }
}
