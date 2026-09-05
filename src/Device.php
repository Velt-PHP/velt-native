<?php

declare(strict_types=1);

namespace Velt\Native;

use Velt\Native\Contracts\NativeBridge;
use Velt\Native\Exceptions\NativeCapabilityException;

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

            if (is_array($decoded)) {
                return $decoded;
            }

            throw new NativeCapabilityException('The native device info is invalid JSON.', 'invalid_native_response');
        }

        if (is_array($info)) {
            return $info;
        }

        throw new NativeCapabilityException('The native device info response is invalid.', 'invalid_native_response');
    }

    public function vibrate(): bool
    {
        $response = $this->bridge->call('Device.Vibrate');

        if (isset($response['error'])) {
            $error = is_array($response['error']) ? $response['error'] : [];
            throw new NativeCapabilityException(
                (string) ($error['message'] ?? 'Unable to vibrate.'),
                (string) ($error['code'] ?? 'native_operation_failed'),
                $error,
            );
        }

        if (!is_bool($response['success'] ?? null)) {
            throw new NativeCapabilityException('The native vibration response is invalid.', 'invalid_native_response');
        }

        return $response['success'];
    }

    /** @return array{success: bool, state: bool} */
    public function toggleFlashlight(): array
    {
        $response = $this->bridge->call('Device.ToggleFlashlight');

        if (isset($response['error'])) {
            $error = is_array($response['error']) ? $response['error'] : [];
            throw new NativeCapabilityException(
                (string) ($error['message'] ?? 'Unable to toggle flashlight.'),
                (string) ($error['code'] ?? 'native_operation_failed'),
                $error,
            );
        }

        if (!is_bool($response['success'] ?? null) || !is_bool($response['state'] ?? null)) {
            throw new NativeCapabilityException(
                'The native flashlight response is invalid.',
                'invalid_native_response',
            );
        }

        return [
            'success' => $response['success'],
            'state' => $response['state'],
        ];
    }
}
