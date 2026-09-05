<?php

declare(strict_types=1);

namespace Velt\Native;

use Velt\Native\Contracts\NativeBridge;
use Velt\Native\Exceptions\NativeCapabilityException;

final class Dialog
{
    public function __construct(private readonly NativeBridge $bridge)
    {
    }

    /** @return array{success: bool} */
    public function show(string $title, string $message): array
    {
        $this->assertText($title, 'title');
        $this->assertText($message, 'message');

        return $this->result($this->bridge->call('Ui.Dialog', [
            'title' => $title,
            'message' => $message,
        ]));
    }

    /** @return array{success: bool} */
    public function toast(string $message): array
    {
        $this->assertText($message, 'message');

        return $this->result($this->bridge->call('Ui.Toast', ['message' => $message]));
    }

    /** @param array<string, mixed> $response */
    private function result(array $response): array
    {
        if (($response['error'] ?? null) !== null) {
            $error = is_array($response['error']) ? $response['error'] : [];
            throw new NativeCapabilityException(
                (string) ($error['message'] ?? 'Native UI operation failed.'),
                (string) ($error['code'] ?? 'native_operation_failed'),
                $error,
            );
        }

        if (!array_key_exists('success', $response) || !is_bool($response['success'])) {
            throw new NativeCapabilityException(
                'The native UI response has an invalid success field.',
                'invalid_native_response',
            );
        }

        return ['success' => $response['success']];
    }

    private function assertText(string $value, string $field): void
    {
        if ($value === '') {
            throw new NativeCapabilityException(
                sprintf('The UI %s must not be empty.', $field),
                'invalid_argument',
            );
        }
    }
}
