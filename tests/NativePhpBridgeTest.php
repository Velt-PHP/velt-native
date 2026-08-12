<?php

declare(strict_types=1);

namespace Velt\Native\Tests;

use PHPUnit\Framework\TestCase;
use Velt\Native\Bridge\NativePhpBridge;
use Velt\Native\Exceptions\NativeBridgeException;

final class NativePhpBridgeTest extends TestCase
{
    public function testItReportsThatTheExtensionIsUnavailableOffDevice(): void
    {
        $bridge = new NativePhpBridge();

        self::assertFalse($bridge->isAvailable());

        $this->expectException(NativeBridgeException::class);
        $this->expectExceptionMessage('NativePHP bridge is unavailable');

        $bridge->call('Device.GetInfo');
    }
}
