<?php

declare(strict_types=1);

namespace Velt\Native\Ui;

use Velt\Native\Contracts\UiProtocolInterface;
use Velt\Native\Exceptions\NativeCapabilityException;

final class UiProtocol implements UiProtocolInterface
{
    public const CURRENT_VERSION = 1;

    public function version(): int
    {
        return self::CURRENT_VERSION;
    }

    public function negotiate(array $peerVersions): int
    {
        foreach ($peerVersions as $version) {
            if (!is_int($version) || $version < 1) {
                throw new NativeCapabilityException('UI protocol versions must be positive integers.', 'invalid_protocol');
            }
        }

        if (!in_array(self::CURRENT_VERSION, $peerVersions, true)) {
            throw new NativeCapabilityException('No compatible UI protocol version was negotiated.', 'protocol_not_supported');
        }

        return self::CURRENT_VERSION;
    }
}
