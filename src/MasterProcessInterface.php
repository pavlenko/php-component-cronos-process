<?php

namespace PE\Component\Cronos\Process;

use PE\Component\Cronos\Process\Traits\TitleAwareInterface;

interface MasterProcessInterface extends TitleAwareInterface
{
    /**
     * @return bool
     */
    public function isShouldTerminate(): bool;

    /**
     * @param string|null $alias
     *
     * @return WorkerControlInterface[]
     */
    public function getChildren(string $alias = null): array;

    /**
     * @param callable $callable
     *
     * @return WorkerControlInterface
     */
    public function fork(callable $callable): ?WorkerControlInterface;

    /**
     * Wait for children exited
     */
    public function wait();

    /**
     * @param int $signal
     */
    public function kill(int $signal = PCNTL::SIGTERM): void;
}
