<?php

declare(strict_types=1);

namespace Velt\Native\Exceptions;

final class NativeCapabilityException extends NativeBridgeException
{
    /** @param array<string, mixed> $details */
    public function __construct(
        string $message,
        private readonly string $codeName,
        private readonly array $details = []
    ) {
        parent::__construct($message);
    }

    public function codeName(): string
    {
        return $this->codeName;
    }

    /** @return array<string, mixed> */
    public function details(): array
    {
        return $this->details;
    }
}
