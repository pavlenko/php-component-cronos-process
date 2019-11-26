<?php

namespace PE\Component\Cronos\Process;

use PE\Component\Cronos\Dispatcher\EventDispatcherInterface;
use PE\Component\Cronos\Process\Traits\TitleAwareInterface;

interface MasterProcessInterface extends EventDispatcherInterface, ProcessInterface, TitleAwareInterface
{
    public const EVENT_MASTER_FORK = 'master-fork';
    public const EVENT_WORKER_EXIT = 'worker-exit';
    public const EVENT_MASTER_EXIT = 'master-exit';

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
