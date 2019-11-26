<?php

namespace PE\Component\Cronos\Process\Tests;

use PE\Component\Cronos\Process\ProcessBase;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProcessBaseTest extends TestCase
{
    use PHPMock;

    private $millis = 0;
    private $microtime;
    private $usleep;

    protected function setUp()
    {
        $this->microtime = $this->getFunctionMock('PE\Component\Cronos\Process', 'microtime');
        $this->microtime->expects(self::any())->willReturnCallback(function () {
            return (++$this->millis) / 1000;
        });

        $this->usleep = $this->getFunctionMock('PE\Component\Cronos\Process', 'usleep');
    }

    public function testSleepMSInterrupted()
    {
        /* @var $base ProcessBase|MockObject */
        $base = $this->getMockForAbstractClass(ProcessBase::class);
        $base->expects(self::once())->method('isShouldTerminate')->willReturn(true);

        self::assertTrue($base->sleepMS(10) > 0);
    }

    public function testSleepMSCompleted()
    {
        $this->usleep->expects(self::atLeastOnce());

        /* @var $base ProcessBase|MockObject */
        $base = $this->getMockForAbstractClass(ProcessBase::class);
        $base->expects(self::atLeastOnce())->method('isShouldTerminate')->willReturn(false);

        self::assertTrue($base->sleepMS(10) == 0);
    }
}
