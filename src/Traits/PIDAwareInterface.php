<?php

namespace PE\Component\Cronos\Process\Traits;

interface PIDAwareInterface
{
    /**
     * @return int
     */
    public function getPID(): int;

    /**
     * @param int $pid
     */
    public function setPID(int $pid): void;
}
