<?php

namespace PE\Component\Cronos\Process;

interface LoopInterface
{
    /**
     * @param float    $seconds
     * @param callable $callable
     *
     * @return TimerInterface
     */
    public function addSingularTimer(float $seconds, callable $callable): TimerInterface;

    /**
     * @param float    $seconds
     * @param callable $callable
     *
     * @return TimerInterface
     */
    public function addPeriodicTimer(float $seconds, callable $callable): TimerInterface;

    /**
     * @param TimerInterface $timer
     */
    public function removeTimer(TimerInterface $timer): void;

    /**
     * Run loop execution
     */
    public function run(): void;

    /**
     * Stop loop execution
     */
    public function stop(): void;
}
