<?php

declare(strict_types=1);

namespace Velt\Native\Runtime;

use Throwable;
use Velt\Kernel\Contracts\ApplicationInterface;
use Velt\Kernel\Contracts\ContainerInterface;
use Velt\Kernel\Contracts\EventDispatcherInterface;
use Velt\Kernel\Contracts\RequestScopeInterface;
use Velt\Kernel\Contracts\RuntimeInterface;
use Velt\Native\Contracts\NativeRuntimeMetricsInterface;
use Velt\Native\Observability\NativeRuntimeMetrics;

/**
 * Adapte le runtime natif aux contrats portables du Kernel.
 *
 * Cette classe ne conserve aucune reference Activity ou Context. La liaison
 * Composer vers le Kernel est locale et reservee aux tests locaux.
 */
final class NativeKernelRuntime implements RuntimeInterface
{
    public function __construct(
        private readonly ApplicationInterface $kernel,
        ?NativeRuntimeMetricsInterface $metrics = null
    ) {
        $this->metrics = $metrics ?? new NativeRuntimeMetrics();
    }

    private readonly NativeRuntimeMetricsInterface $metrics;

    public function metrics(): NativeRuntimeMetricsInterface
    {
        return $this->metrics;
    }

    public function container(): ContainerInterface
    {
        return $this->kernel->container();
    }

    public function events(): EventDispatcherInterface
    {
        return $this->kernel->events();
    }

    public function requestScope(): RequestScopeInterface
    {
        return $this->kernel->requestScope();
    }

    public function boot(): void
    {
        $this->kernel->boot();
    }

    public function ready(): void
    {
        $this->kernel->ready();
    }

    public function bootstrap(): void
    {
        $this->metrics->startBoot();

        try {
            $this->kernel->bootstrap();
        } finally {
            $this->metrics->finishBoot();
        }
    }

    public function handle(mixed $input = null): mixed
    {
        return $this->metrics->measureCall(
            fn (): mixed => $this->kernel->handle($input)
        );
    }

    public function terminate(mixed $input = null, mixed $output = null): void
    {
        $this->kernel->terminate($input, $output);
    }

    public function fail(Throwable $exception, string $phase = 'runtime'): void
    {
        $this->kernel->fail($exception, $phase);
    }

    public function pause(): void
    {
        $this->kernel->pause();
    }

    public function resume(): void
    {
        $this->kernel->resume();
    }

    public function reset(): void
    {
        $this->kernel->reset();
    }

    public function shutdown(): void
    {
        $this->kernel->shutdown();
    }

    public function isReady(): bool
    {
        return $this->kernel->isReady();
    }

    public function isPaused(): bool
    {
        return $this->kernel->isPaused();
    }

    public function isShutdown(): bool
    {
        return $this->kernel->isShutdown();
    }

    public function isBootstrapped(): bool
    {
        return $this->kernel->isBootstrapped();
    }

    public function isTerminated(): bool
    {
        return $this->kernel->isTerminated();
    }
}
