<?php

declare(strict_types=1);

namespace Velt\Native\Ui;

use Velt\Native\Exceptions\NativeCapabilityException;

final class UiDocument
{
    public function __construct(private readonly UiNode $root, private readonly UiProtocol $protocol = new UiProtocol())
    {
    }

    /** @return array{protocol_version: int, root: array<string, mixed>} */
    public function toArray(): array
    {
        return ['protocol_version' => $this->protocol->version(), 'root' => $this->root->toArray()];
    }

    public function negotiate(array $peerVersions): void
    {
        $this->protocol->negotiate($peerVersions);
    }
}
