<?php

declare(strict_types=1);

namespace Velt\Native\Bridge;

use JsonException;
use Velt\Native\Contracts\NativeBridge;
use Velt\Native\Exceptions\NativeBridgeException;

final class NativePhpBridge implements NativeBridge
{
    public function isAvailable(): bool
    {
        return function_exists('nativephp_call');
    }

    public function call(string $method, array $parameters = []): array
    {
        if (!$this->isAvailable()) {
            throw new NativeBridgeException(
                'The NativePHP bridge is unavailable. Run this operation inside the Velt Android runtime or configure a fake bridge for tests.',
            );
        }

        if (!preg_match('/^[A-Za-z][A-Za-z0-9_]*(?:\.[A-Za-z][A-Za-z0-9_]*)+$/', $method)) {
            throw new NativeBridgeException(sprintf('Invalid native bridge method "%s".', $method));
        }

        try {
            $payload = json_encode($parameters, JSON_THROW_ON_ERROR);
            $response = nativephp_call($method, $payload);
        } catch (JsonException $exception) {
            throw new NativeBridgeException('Unable to encode native bridge parameters.', previous: $exception);
        }

        if ($response === null || $response === '') {
            return [];
        }

        if (!is_string($response)) {
            throw new NativeBridgeException('The native bridge returned an unsupported response type.');
        }

        try {
            $decoded = json_decode($response, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new NativeBridgeException('The native bridge returned invalid JSON.', previous: $exception);
        }

        if (!is_array($decoded)) {
            throw new NativeBridgeException('The native bridge response must be a JSON object.');
        }

        return $decoded;
    }
}
