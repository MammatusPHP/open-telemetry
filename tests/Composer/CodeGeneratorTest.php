<?php

declare(strict_types=1);

namespace Mammatus\Tests\OpenTelemetry\Composer;

use Composer\Factory;
use Composer\IO\NullIO;
use Composer\Script\ScriptEvents;
use Mammatus\OpenTelemetry\Composer\CodeGenerator;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;
use WyriHaximus\Composer\GenerativePluginTooling\Helper\Order;

use function dirname;

final class CodeGeneratorTest extends AsyncTestCase
{
    use RestoresComposerHome;

    #[Test]
    public function getSubscribedEvents(): void
    {
        self::assertSame(
            [
                ScriptEvents::PRE_AUTOLOAD_DUMP => [
                    ['registerWithSpans', Order::LATE],
                    ['findMonkeysToPatch', Order::EVERYONE_ALSO_MUST_TO_GO_BEFORE_ME],
                ],
            ],
            CodeGenerator::getSubscribedEvents(),
        );
    }

    #[Test]
    #[DoesNotPerformAssertions]
    public function activateDeactivateAndUninstall(): void
    {
        $this->swapComposerHome($this->getTmpDir());

        $composer = Factory::create(new NullIO(), dirname(__DIR__, 2) . '/composer.json');
        $io       = new NullIO();
        $plugin   = new CodeGenerator();

        $plugin->activate($composer, $io);
        $plugin->deactivate($composer, $io);
        $plugin->uninstall($composer, $io);
    }
}
