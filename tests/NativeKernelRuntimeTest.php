<?php

declare(strict_types=1);

namespace Velt\Native\Tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Velt\Kernel\Application;
use Velt\Kernel\Contracts\RuntimeInterface;
use Velt\Native\Runtime\NativeKernelRuntime;

final class NativeKernelRuntimeTest extends TestCase
{
    public function test_adapter_delegates_portable_lifecycle_to_kernel(): void
    {
        $runtime = new NativeKernelRuntime(new Application(__DIR__));

        self::assertInstanceOf(RuntimeInterface::class, $runtime);
        $runtime->bootstrap();
        self::assertTrue($runtime->isReady());

        $runtime->pause();
        self::assertTrue($runtime->isPaused());
        $runtime->resume();
        self::assertFalse($runtime->isPaused());

        $runtime->reset();
        self::assertTrue($runtime->isReady());
    }

    public function test_adapter_does_not_restart_kernel_after_fatal_failure(): void
    {
        $runtime = new NativeKernelRuntime(new Application(__DIR__));
        $runtime->bootstrap();

        $runtime->fail(new RuntimeException('fatal'), 'native.handle');

        self::assertTrue($runtime->isShutdown());
        self::assertFalse($runtime->isReady());
    }

    public function test_adapter_survives_one_thousand_local_interactions(): void
    {
        $runtime = new NativeKernelRuntime(new Application(__DIR__));

        for ($interaction = 0; $interaction < 1000; $interaction++) {
            self::assertSame($interaction, $runtime->handle($interaction));
        }

        self::assertSame(1000, $runtime->metrics()->export()['call_count']);
        self::assertFalse($runtime->isShutdown());
    }
}
