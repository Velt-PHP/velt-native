<?php

declare(strict_types=1);

namespace Velt\Native\Tests;

use PHPUnit\Framework\TestCase;
use Velt\Native\Contracts\NativeRuntimeMetricsInterface;
use Velt\Native\Observability\NativeRuntimeMetrics;

final class NativeRuntimeMetricsTest extends TestCase
{
    public function test_metrics_are_exportable_for_boot_calls_callbacks_and_anrs(): void
    {
        $metrics = new NativeRuntimeMetrics();

        $metrics->startBoot();
        $metrics->finishBoot();
        $metrics->measureCall(static fn (): string => 'call');
        $metrics->measureCallback(static fn (): string => 'callback');
        $metrics->recordAnr();

        $export = $metrics->export();

        self::assertInstanceOf(NativeRuntimeMetricsInterface::class, $metrics);
        self::assertIsInt($export['boot_duration_ns']);
        self::assertSame(1, $export['call_count']);
        self::assertSame(1, $export['callback_count']);
        self::assertSame(1, $export['anr_count']);
        self::assertGreaterThan(0, $export['memory_bytes']);
    }
}
