<?php

namespace PE\Component\Cronos\Process;

abstract class ProcessBase implements ProcessInterface
{
    /**
     * @inheritDoc
     */
    public function sleepMS(int $millis): int
    {
        $start = microtime(true);
        $delay = $millis / 1000;

        while ((microtime(true) - $start) < $delay) {
            if ($this->isShouldTerminate()) {
                return $millis;
            }

            $millis--;

            usleep(1000);
        }

        return 0;
    }
}
