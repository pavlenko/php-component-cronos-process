<?php

namespace PE\Component\Cronos\Process;

interface ProcessInterface
{
    /**
     * @return bool
     */
    public function isShouldTerminate(): bool;

    /**
     * @param int $millis
     *
     * @return int Left milliseconds to sleep if interrupted, 0 on success
     */
    public function sleepMS(int $millis): int;
}
