<?php

namespace PE\Component\Cronos\Process;

final class Loop implements LoopInterface
{
    /**
     * @var bool
     */
    private $running = false;

    /**
     * @var bool
     */
    private $sorted = false;

    /**
     * @var TimerInterface[]
     */
    private $timers = [];

    /**
     * @var float[]
     */
    private $schedule = [];

    public function __construct()
    {}

    /**
     * @inheritDoc
     */
    public function addSingularTimer(float $seconds, callable $callable): TimerInterface
    {
        return $this->createTimer($seconds, $callable, false);
    }

    /**
     * @inheritDoc
     */
    public function addPeriodicTimer(float $seconds, callable $callable): TimerInterface
    {
        return $this->createTimer($seconds, $callable, true);
    }

    /**
     * @param float    $seconds
     * @param callable $callable
     * @param bool     $periodic
     *
     * @return TimerInterface
     */
    private function createTimer(float $seconds, callable $callable, bool $periodic): TimerInterface
    {
        $timer = new Timer($seconds, $callable, $periodic);

        $id = spl_object_hash($timer);

        $schedule = microtime(true) + $timer->getInterval();

        $timer->setSchedule($schedule);

        $this->timers[$id]   = $timer;
        $this->schedule[$id] = $schedule;

        $this->sorted = false;

        return $timer;
    }

    /**
     * @inheritDoc
     */
    public function removeTimer(TimerInterface $timer): void
    {
        $id = spl_object_hash($timer);

        unset($this->timers[$id], $this->schedule[$id]);
    }

    /**
     * @inheritDoc
     */
    public function run(): void
    {
        $this->running = true;

        while ($this->running) {
            usleep(1000);

            if (!$this->sorted) {
                $this->sorted = true;
                asort($this->schedule);
            }

            $time = microtime(true);

            foreach ($this->schedule as $id => $scheduled) {
                if ($scheduled > $time) {
                    // schedule is sorted so we can safely can break
                    break;
                }

                if (!isset($this->schedule[$id]) || $this->schedule[$id] !== $scheduled) {
                    // If timer removed while we loop - skip the current schedule
                    // @codeCoverageIgnoreStart
                    continue;
                    // @codeCoverageIgnoreEnd
                }

                // Get timer for ensure it not deleted before call
                $timer = $this->timers[$id];

                call_user_func($timer->getCallable(), $timer);

                // Re-schedule periodic timers and delete singular
                if ($timer->isPeriodic() && isset($this->timers[$id])) {
                    $timer->setSchedule($timer->getSchedule() + $timer->getInterval());

                    $this->schedule[$id] = $timer->getSchedule();
                    $this->sorted        = false;
                } else {
                    unset($this->timers[$id], $this->schedule[$id]);
                }
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function stop(): void
    {
        $this->running = false;
    }
}
