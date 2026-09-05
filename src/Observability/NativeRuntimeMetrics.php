<?php

declare(strict_types=1);

namespace Velt\Native\Observability;

use Velt\Native\Contracts\NativeRuntimeMetricsInterface;

/**
 * Metriques PHP du runtime natif.
 *
 * La detection reelle des ANR appartient au runtime Android, qui appelle
 * recordAnr() lorsqu'un seuil instrumente est depasse.
 */
final class NativeRuntimeMetrics implements NativeRuntimeMetricsInterface
{
    private ?int $bootStartedAt = null;
    private ?int $bootDuration = null;
    private int $callCount = 0;
    private int $callbackCount = 0;
    private int $callDuration = 0;
    private int $callbackDuration = 0;
    private int $anrCount = 0;

    public function startBoot(): void
    {
        $this->bootStartedAt = hrtime(true);
    }

    public function finishBoot(): void
    {
        if ($this->bootStartedAt === null) {
            return;
        }

        $this->bootDuration = hrtime(true) - $this->bootStartedAt;
        $this->bootStartedAt = null;
    }

    public function measureCall(callable $operation): mixed
    {
        return $this->measure($operation, $this->callCount, $this->callDuration);
    }

    public function measureCallback(callable $operation): mixed
    {
        return $this->measure($operation, $this->callbackCount, $this->callbackDuration);
    }

    public function recordAnr(): void
    {
        $this->anrCount++;
    }

    public function export(): array
    {
        return [
            'boot_duration_ns' => $this->bootDuration,
            'memory_bytes' => memory_get_usage(true),
            'call_count' => $this->callCount,
            'call_latency_avg_ns' => $this->average($this->callDuration, $this->callCount),
            'callback_count' => $this->callbackCount,
            'callback_latency_avg_ns' => $this->average($this->callbackDuration, $this->callbackCount),
            'anr_count' => $this->anrCount,
        ];
    }

    /** @param callable(): mixed $operation */
    private function measure(callable $operation, int &$count, int &$duration): mixed
    {
        $startedAt = hrtime(true);

        try {
            return $operation();
        } finally {
            $count++;
            $duration += hrtime(true) - $startedAt;
        }
    }

    private function average(int $duration, int $count): ?float
    {
        return $count === 0 ? null : $duration / $count;
    }
}
