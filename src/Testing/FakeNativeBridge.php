<?php

declare(strict_types=1);

namespace Velt\Native\Testing;

use Velt\Native\Contracts\NativeBridge;
use Velt\Native\Exceptions\NativeBridgeException;

final class FakeNativeBridge implements NativeBridge
{
    /** @var list<array{method: string, parameters: array<string, mixed>}> */
    private array $calls = [];

    /** @var array<string, list<array<string, mixed>>> */
    private array $responses = [];

    public function isAvailable(): bool
    {
        return true;
    }

    /** @param array<string, mixed> $response */
    public function respondWith(string $method, array $response): self
    {
        $this->responses[$method][] = $response;

        return $this;
    }

    public function call(string $method, array $parameters = []): array
    {
        $this->calls[] = ['method' => $method, 'parameters' => $parameters];

        if (($this->responses[$method] ?? []) === []) {
            throw new NativeBridgeException(sprintf('No fake response was registered for "%s".', $method));
        }

        return array_shift($this->responses[$method]);
    }

    /** @return list<array{method: string, parameters: array<string, mixed>}> */
    public function calls(): array
    {
        return $this->calls;
    }
}
