<?php

namespace PE\Component\Cronos\Process;

interface TimerInterface
{
    /**
     * @return float
     */
    public function getInterval(): float;

    /**
     * @return callable
     */
    public function getCallable(): callable;

    /**
     * @return bool
     */
    public function isPeriodic(): bool;

    /**
     * @return float
     */
    public function getSchedule(): float;

    /**
     * @param float $schedule
     */
    public function setSchedule(float $schedule): void;
}
