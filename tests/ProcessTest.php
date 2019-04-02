<?php

namespace PE\Component\Cronos\Process\Tests;

use PE\Component\Cronos\Process\PCNTL;
use PE\Component\Cronos\Process\POSIX;
use PE\Component\Cronos\Process\Process;
use PE\Component\Cronos\Process\Signals;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProcessTest extends TestCase
{
    /**
     * @var POSIX|MockObject
     */
    private $posix;

    /**
     * @var PCNTL|MockObject
     */
    private $pcntl;

    /**
     * @var Signals|MockObject
     */
    private $signals;

    /**
     * @var Process
     */
    private $process;

    protected function setUp()
    {
        $this->posix   = $this->createMock(POSIX::class);
        $this->pcntl   = $this->createMock(PCNTL::class);
        $this->signals = $this->createMock(Signals::class);
        $this->process = new Process();
        $this->process->setSignals($this->signals);

        POSIX::setInstance($this->posix);
        PCNTL::setInstance($this->pcntl);
    }

    public function testGetSetSignals(): void
    {
        $process = new Process();
        $signals = new Signals();

        self::assertNotSame($signals, $process->getSignals());

        $process->setSignals($signals);

        self::assertSame($signals, $process->getSignals());
    }

    public function testRunError(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->pcntl->expects(self::never())->method('setAsyncSignalsEnabled');

        $this->process->run();
    }

    public function testRunSuccess(): void
    {
        $this->pcntl->expects(self::once())->method('setAsyncSignalsEnabled')->with(true);

        $this->signals
            ->expects(self::once())
            ->method('registerHandler')
            ->with(PCNTL::SIGTERM, [$this->process, 'onSignalTerminate']);

        /* @var $callable callable|MockObject */
        $callable = $this->getMockBuilder(\stdClass::class)->setMethods(['__invoke'])->getMock();
        $callable->expects(static::once())->method('__invoke')->with($this->process);

        $this->process->setCallable($callable);
        $this->process->run();
    }

    public function testForkErrorCallable(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->pcntl->expects(self::never())->method('fork');

        $this->process->fork(new Process());
    }

    public function testForkErrorPCNTL(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->pcntl->expects(self::once())->method('fork')->willReturn(-1);

        $child = new Process();
        $child->setCallable(function(){});

        $this->process->fork($child);
    }

    public function testForkParent(): void
    {
        $this->pcntl->expects(self::once())->method('fork')->willReturn(1000);

        $this->signals
            ->expects(self::exactly(2))
            ->method('registerHandler')
            ->withConsecutive(
                [PCNTL::SIGTERM, [$this->process, 'onSignalTerminate']],
                [PCNTL::SIGCHLD, [$this->process, 'onSignalFromChild']]
            );

        $child = new Process();
        $child->setCallable(function(){});

        $this->process->fork($child);

        self::assertSame(1000, $child->getPID());
    }

    public function testForkChild(): void
    {
        $this->pcntl->expects(self::once())->method('fork')->willReturn(0);

        /* @var $child Process|MockObject */
        $child = $this->createMock(Process::class);
        $child->expects(self::once())->method('getCallable')->willReturn(function(){});
        $child->expects(self::once())->method('setPID');
        $child->expects(self::once())->method('run');
        $child->expects(self::once())->method('exit');

        $this->process->fork($child);
    }

    public function testOnSignalTerminate(): void
    {
        $this->pcntl->expects(self::once())->method('fork')->willReturn(1000);

        /* @var $child Process|MockObject */
        $child = $this->createPartialMock(Process::class, ['kill']);
        $child->setCallable(function(){});
        $child->expects(self::once())->method('kill');

        $this->process->fork($child);

        $this->process->onSignalTerminate();

        self::assertTrue($this->process->isShouldTerminate());
    }

    public function testOnSignalFromChild(): void
    {
        $this->pcntl->expects(self::once())->method('fork')->willReturn(1000);

        $this->pcntl->expects(self::exactly(2))->method('waitPID')->willReturnOnConsecutiveCalls(1000, 0);

        $child = new Process();
        $child->setCallable(function(){});

        $this->process->fork($child);

        self::assertCount(1, $this->process->getChildren());

        $this->process->onSignalFromChild();

        self::assertCount(0, $this->process->getChildren());
    }

    public function testWait(): void
    {
        $this->pcntl->expects(self::once())->method('fork')->willReturn(1000);
        $this->pcntl->expects(self::exactly(2))->method('waitPID')->willReturnOnConsecutiveCalls(1000, 0);

        $this->signals->expects(self::once())->method('dispatch')->willReturnCallback(function () {
            $this->process->onSignalFromChild();
        });

        $child = new Process();
        $child->setCallable(function(){});

        $this->process->fork($child);
        $this->process->wait();
    }

    public function testKill(): void
    {
        $this->posix->expects(self::once())->method('kill')->with(1000, PCNTL::SIGTERM);
        $this->process->setPID(1000);
        $this->process->kill();
    }

    public function testDispatch(): void
    {
        $this->signals->expects(self::once())->method('dispatch');
        $this->process->dispatch();
    }

    public function testLazySetTitle()
    {
        $this->pcntl->expects(self::once())->method('setAsyncSignalsEnabled')->with(true);

        $this->signals->expects(self::once())->method('registerHandler');

        /* @var $process Process|MockObject */
        $process = $this->createTestProxy(Process::class);
        $process->setProcessTitle('FOO');
        $process->setSignals($this->signals);

        $process->method('setProcessTitle')->with('FOO');

        /* @var $callable callable|MockObject */
        $callable = $this->getMockBuilder(\stdClass::class)->setMethods(['__invoke'])->getMock();
        $callable->expects(static::once())->method('__invoke');

        $process->setCallable($callable);
        $process->run();
    }

    public function testLazySetTitleInFork()
    {
        $this->pcntl->method('fork')->willReturn(1000);

        $this->signals->method('registerHandler');

        /* @var $process Process|MockObject */
        $process = $this->createTestProxy(Process::class);
        $process->setProcessTitle('FOO');
        $process->setSignals($this->signals);

        $process->method('setProcessTitle')->with('FOO');

        /* @var $child Process|MockObject */
        $child = $this->createTestProxy(Process::class);
        $child->setCallable(function () {});

        $child->expects(self::once())->method('setPID');

        $process->fork($child);
    }
}
