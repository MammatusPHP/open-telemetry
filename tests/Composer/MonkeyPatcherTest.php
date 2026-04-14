<?php

declare(strict_types=1);

namespace Mammatus\Tests\OpenTelemetry\Composer;

use Composer\Factory;
use Composer\IO\NullIO;
use Composer\Script\ScriptEvents;
use Mammatus\OpenTelemetry\Composer\MonkeyPatcher;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WyriHaximus\Composer\GenerativePluginTooling\Helper\Order;

use function dirname;

final class MonkeyPatcherTest extends TestCase
{
    #[Test]
    public function getSubscribedEvents(): void
    {
        self::assertSame(
            [ScriptEvents::PRE_AUTOLOAD_DUMP => ['findMonkeysToPatch', Order::EVERYONE_ALSO_MUST_TO_GO_BEFORE_ME]],
            MonkeyPatcher::getSubscribedEvents(),
        );
    }

    #[Test]
    public function activateDeactivateAndUninstall(): void
    {
        $composer = Factory::create(new NullIO(), dirname(__DIR__, 2) . '/composer.json');
        $io       = new NullIO();
        $plugin   = new MonkeyPatcher();

        $plugin->activate($composer, $io);
        $plugin->deactivate($composer, $io);
        $plugin->uninstall($composer, $io);

        $this->expectNotToPerformAssertions();
    }
}
