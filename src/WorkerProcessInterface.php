<?php

namespace PE\Component\Cronos\Process;

use PE\Component\Cronos\Process\Traits\PIDAwareInterface;
use PE\Component\Cronos\Process\Traits\TitleAwareInterface;

interface WorkerProcessInterface extends PIDAwareInterface, TitleAwareInterface
{
    /**
     * @return bool
     */
    public function isShouldTerminate(): bool;

    /**
     * Execute logic
     */
    public function work(): void;

    /**
     * @param int $code
     */
    public function exit(int $code = 0): void;
}
