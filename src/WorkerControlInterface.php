<?php

namespace PE\Component\Cronos\Process;

use PE\Component\Cronos\Process\Traits\PIDAwareInterface;

interface WorkerControlInterface extends PIDAwareInterface
{
    /**
     * @return string
     */
    public function getAlias(): string;

    /**
     * @param string $alias
     */
    public function setAlias(string $alias): void;

    /**
     * @param int $signal
     */
    public function kill(int $signal = PCNTL::SIGTERM): void;

    /**
     * @param int $code
     */
    public function exit(int $code = 0): void;
}
