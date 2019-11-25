<?php

namespace PE\Component\Cronos\Process;

use PE\Component\Cronos\Process\Traits\PIDAwareInterface;
use PE\Component\Cronos\Process\Traits\TitleAwareInterface;

interface WorkerProcessInterface extends PIDAwareInterface, ProcessInterface, TitleAwareInterface
{
    /**
     * Execute logic
     */
    public function work(): void;

    /**
     * @param int $code
     */
    public function exit(int $code = 0): void;
}
