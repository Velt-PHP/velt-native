<?php

declare(strict_types=1);

namespace Velt\Native\Contracts;

interface NativeBridge
{
    /**
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    public function call(string $method, array $parameters = []): array;

    public function isAvailable(): bool;
}
