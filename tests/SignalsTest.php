<?php

namespace PE\Component\Cronos\Process\Tests;

use PE\Component\Cronos\Process\PCNTL;
use PE\Component\Cronos\Process\Signals;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SignalsTest extends TestCase
{
    /**
     * @var PCNTL|MockObject
     */
    private $pcntl;

    /**
     * @var Signals
     */
    private $signals;

    protected function setUp()
    {
        $this->pcntl   = $this->createMock(PCNTL::class);
        $this->signals = new Signals();

        PCNTL::setInstance($this->pcntl);
    }

    public function testRegisterHandlerError(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->pcntl
            ->expects(static::once())
            ->method('setSignalHandler')
            ->with(PCNTL::SIGTERM, [$this->signals, 'enqueue'])
            ->willReturn(false);

        $this->signals->registerHandler(PCNTL::SIGTERM, function(){});
    }

    public function testRegisterHandler(): void
    {
        $this->pcntl
            ->expects(static::once())
            ->method('setSignalHandler')
            ->with(PCNTL::SIGTERM, [$this->signals, 'enqueue'])
            ->willReturn(true);

        $this->signals->registerHandler(PCNTL::SIGTERM, function(){});
    }

    public function testDispatch(): void
    {
        $this->pcntl
            ->expects(static::once())
            ->method('setSignalHandler')
            ->with(PCNTL::SIGTERM, [$this->signals, 'enqueue'])
            ->willReturn(true);

        $this->pcntl
            ->expects(static::once())
            ->method('dispatch')
            ->willReturnCallback(function () {
                $this->signals->enqueue(PCNTL::SIGTERM);
                return true;
            });

        /* @var $listener callable|MockObject */
        $listener = $this->getMockBuilder(\stdClass::class)->setMethods(['__invoke'])->getMock();
        $listener->expects(static::once())->method('__invoke');

        $this->signals->registerHandler(PCNTL::SIGTERM, $listener);
        $this->signals->dispatch();
    }
}
