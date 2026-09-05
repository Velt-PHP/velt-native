<?php

declare(strict_types=1);

namespace Velt\Native;

use Velt\Native\Contracts\NativeBridge;
use Velt\Native\Exceptions\NativeCapabilityException;

final class SandboxFileStore
{
    public function __construct(private readonly NativeBridge $bridge)
    {
    }

    public function read(string $path): string
    {
        $response = $this->bridge->call('Storage.ReadFile', ['path' => $this->path($path)]);

        if (isset($response['error'])) {
            throw $this->error($response, 'Unable to read sandbox file.');
        }

        if (!is_string($response['contents'] ?? null)) {
            throw new NativeCapabilityException('The native file response has no contents.', 'invalid_native_response');
        }

        return $response['contents'];
    }

    public function write(string $path, string $contents): void
    {
        $response = $this->bridge->call('Storage.WriteFile', [
            'path' => $this->path($path),
            'contents' => $contents,
        ]);

        $this->assertSuccess($response, 'Unable to write sandbox file.');
    }

    public function delete(string $path): void
    {
        $response = $this->bridge->call('Storage.DeleteFile', ['path' => $this->path($path)]);

        $this->assertSuccess($response, 'Unable to delete sandbox file.');
    }

    private function path(string $path): string
    {
        if ($path === '' || str_contains($path, "\0") || str_starts_with($path, '/') || preg_match('/^[A-Za-z]:[\\\\\/]/', $path)) {
            throw new NativeCapabilityException('The sandbox path must be relative and non-empty.', 'invalid_path');
        }

        $segments = explode('/', str_replace('\\', '/', $path));
        if (in_array('..', $segments, true)) {
            throw new NativeCapabilityException('The sandbox path must stay inside the application sandbox.', 'invalid_path');
        }

        return implode('/', $segments);
    }

    /** @param array<string, mixed> $response */
    private function assertSuccess(array $response, string $message): void
    {
        if (isset($response['error'])) {
            throw $this->error($response, $message);
        }

        if (($response['success'] ?? null) !== true) {
            throw new NativeCapabilityException($message, 'native_operation_failed');
        }
    }

    /** @param array<string, mixed> $response */
    private function error(array $response, string $fallback): NativeCapabilityException
    {
        $error = is_array($response['error'] ?? null) ? $response['error'] : [];

        return new NativeCapabilityException(
            (string) ($error['message'] ?? $fallback),
            (string) ($error['code'] ?? 'native_operation_failed'),
            $error,
        );
    }
}
