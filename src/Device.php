<?php

declare(strict_types=1);

namespace Velt\Native;

use Velt\Native\Contracts\NativeBridge;

final class Device
{
    public function __construct(private readonly NativeBridge $bridge)
    {
    }

    /** @return array<string, mixed> */
    public function info(): array
    {
        $response = $this->bridge->call('Device.GetInfo');
        $info = $response['info'] ?? [];

        if (is_string($info)) {
            $decoded = json_decode($info, true);

            return is_array($decoded) ? $decoded : [];
        }

        return is_array($info) ? $info : [];
    }

    public function vibrate(): bool
    {
        return ($this->bridge->call('Device.Vibrate')['success'] ?? false) === true;
    }

    /** @return array{success: bool, state: bool} */
    public function toggleFlashlight(): array
    {
        $response = $this->bridge->call('Device.ToggleFlashlight');

        return [
            'success' => ($response['success'] ?? false) === true,
            'state' => ($response['state'] ?? false) === true,
        ];
    }
}
