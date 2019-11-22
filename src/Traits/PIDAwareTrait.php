<?php

namespace PE\Component\Cronos\Process\Traits;

/**
 * @codeCoverageIgnore
 */
trait PIDAwareTrait
{
    /**
     * @var int
     */
    private $pid;

    /**
     * @return int
     */
    public function getPID(): int
    {
        return (int) $this->pid;
    }

    /**
     * @param int $pid
     */
    public function setPID(int $pid): void
    {
        $this->pid = $pid;
    }
}
