<?php

namespace PE\Component\Cronos\Process\Tests;

use PE\Component\Cronos\Process\Loop;
use PE\Component\Cronos\Process\TimerInterface;
use PHPUnit\Framework\TestCase;

class LoopTest extends TestCase
{
    public function testAddSingularTimer()
    {
        $callable = function () {};

        $timer = (new Loop())->addSingularTimer(0.1, $callable);

        self::assertInstanceOf(TimerInterface::class, $timer);

        self::assertSame(0.1, $timer->getInterval());
        self::assertSame($callable, $timer->getCallable());
        self::assertFalse($timer->isPeriodic());
    }

    public function testAddPeriodicTimer()
    {
        $callable = function () {};

        $timer = (new Loop())->addPeriodicTimer(0.1, $callable);

        self::assertInstanceOf(TimerInterface::class, $timer);

        self::assertSame(0.1, $timer->getInterval());
        self::assertSame($callable, $timer->getCallable());
        self::assertTrue($timer->isPeriodic());
    }

    public function testExecutePeriodicTimerUntilRemoved()
    {
        $executed = 0;

        $loop = new Loop();
        $loop->addPeriodicTimer(0.1, function (TimerInterface $timer) use ($loop, &$executed) {
            if ($executed == 3) {
                $loop->removeTimer($timer);
                $loop->stop();
                return;
            }

            $executed++;
        });
        $loop->run();

        self::assertSame(3, $executed);
    }
}
