<?php

namespace PE\Component\Cronos\Process;

final class Timer implements TimerInterface
{
    /**
     * @var float
     */
    private $interval;

    /**
     * @var callable
     */
    private $callable;

    /**
     * @var bool
     */
    private $periodic;

    /**
     * @var float
     */
    private $schedule;

    /**
     * @param float    $interval
     * @param callable $callable
     * @param bool     $periodic
     */
    public function __construct(float $interval, callable $callable, bool $periodic)
    {
        $this->interval = $interval;
        $this->callable = $callable;
        $this->periodic = $periodic;
    }

    /**
     * @inheritDoc
     */
    public function getInterval(): float
    {
        return $this->interval;
    }

    /**
     * @inheritDoc
     */
    public function getCallable(): callable
    {
        return $this->callable;
    }

    /**
     * @inheritDoc
     */
    public function isPeriodic(): bool
    {
        return $this->periodic;
    }

    /**
     * @inheritDoc
     */
    public function getSchedule(): float
    {
        return $this->schedule;
    }

    /**
     * @inheritDoc
     */
    public function setSchedule(float $schedule): void
    {
        $this->schedule = $schedule;
    }
}
