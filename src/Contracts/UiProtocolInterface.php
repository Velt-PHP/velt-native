<?php

declare(strict_types=1);

namespace Velt\Native\Contracts;

interface UiProtocolInterface
{
    public function version(): int;

    /** @param list<int> $peerVersions */
    public function negotiate(array $peerVersions): int;
}
