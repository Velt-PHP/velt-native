<?php

declare(strict_types=1);

namespace Velt\Native\Tests;

use PHPUnit\Framework\TestCase;
use Velt\Native\Device;
use Velt\Native\Testing\FakeNativeBridge;

final class DeviceTest extends TestCase
{
    public function testItReadsDeviceInfoReturnedByTheNativePhpContract(): void
    {
        $bridge = (new FakeNativeBridge())->respondWith('Device.GetInfo', [
            'info' => json_encode(['platform' => 'android', 'model' => 'Pixel']),
        ]);

        self::assertSame(
            ['platform' => 'android', 'model' => 'Pixel'],
            (new Device($bridge))->info(),
        );
    }

    public function testItCallsNativeDeviceOperations(): void
    {
        $bridge = (new FakeNativeBridge())
            ->respondWith('Device.Vibrate', ['success' => true])
            ->respondWith('Device.ToggleFlashlight', ['success' => true, 'state' => true]);
        $device = new Device($bridge);

        self::assertTrue($device->vibrate());
        self::assertSame(['success' => true, 'state' => true], $device->toggleFlashlight());
        self::assertSame(
            [
                ['method' => 'Device.Vibrate', 'parameters' => []],
                ['method' => 'Device.ToggleFlashlight', 'parameters' => []],
            ],
            $bridge->calls(),
        );
    }
}
