<?php

declare(strict_types=1);

namespace Velt\Native\Contracts;

/**
 * Collecteur de metriques du runtime natif, exportable sans Android.
 */
interface NativeRuntimeMetricsInterface
{
    public function startBoot(): void;
    public function finishBoot(): void;

    /** @param callable(): mixed $operation */
    public function measureCall(callable $operation): mixed;

    /** @param callable(): mixed $operation */
    public function measureCallback(callable $operation): mixed;

    public function recordAnr(): void;

    /** @return array<string, int|float|null> */
    public function export(): array;
}
