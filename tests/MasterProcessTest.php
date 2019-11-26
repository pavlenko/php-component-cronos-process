<?php

namespace PE\Component\Cronos\Process\Tests;

use PE\Component\Cronos\Process\FactoryInterface;
use PE\Component\Cronos\Process\MasterProcess;
use PE\Component\Cronos\Process\PCNTL;
use PE\Component\Cronos\Process\SignalHandlerInterface;
use PE\Component\Cronos\Process\WorkerProcessInterface;
use PE\Component\Cronos\Process\WorkerControlInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MasterProcessTest extends TestCase
{
    private $pcntlOriginal;
    private $pcntlMock;

    protected function setUp()
    {
        $this->pcntlOriginal = PCNTL::getInstance();
        $this->pcntlMock     = $this->createMock(PCNTL::class);

        PCNTL::setInstance($this->pcntlMock);
    }

    protected function tearDown()
    {
        PCNTL::setInstance($this->pcntlOriginal);
    }

    public function testConstructor()
    {
        /* @var $handler SignalHandlerInterface|MockObject */
        $handler = $this->createMock(SignalHandlerInterface::class);
        $handler->expects(self::exactly(3))->method('attachListener');

        /* @var $factory FactoryInterface|MockObject */
        $factory = $this->createMock(FactoryInterface::class);

        new MasterProcess($factory, $handler);
    }

    public function testIsShouldTerminateReturnsFalse()
    {
        /* @var $handler SignalHandlerInterface|MockObject */
        $handler = $this->createMock(SignalHandlerInterface::class);
        $handler->expects(self::once())->method('dispatch');

        /* @var $factory FactoryInterface|MockObject */
        $factory = $this->createMock(FactoryInterface::class);

        $master = new MasterProcess($factory, $handler);

        self::assertFalse($master->isShouldTerminate());
    }

    public function testIsShouldTerminateReturnsTrue()
    {
        /* @var $handler SignalHandlerInterface|MockObject */
        $handler = $this->createMock(SignalHandlerInterface::class);

        /* @var $factory FactoryInterface|MockObject */
        $factory = $this->createMock(FactoryInterface::class);

        $master = new MasterProcess($factory, $handler);
        $master->kill();

        self::assertTrue($master->isShouldTerminate());
    }

    public function testForkFailure()
    {
        /* @var $handler SignalHandlerInterface|MockObject */
        $handler = $this->createMock(SignalHandlerInterface::class);

        /* @var $factory FactoryInterface|MockObject */
        $factory = $this->createMock(FactoryInterface::class);

        $this->pcntlMock->expects(self::once())->method('fork')->willReturn(-1);

        $this->expectException(\RuntimeException::class);

        $master = new MasterProcess($factory, $handler);
        $master->fork(function () {});
    }

    public function testForkSuccessWorkerSide()
    {
        /* @var $handler SignalHandlerInterface|MockObject */
        $handler = $this->createMock(SignalHandlerInterface::class);

        /* @var $factory FactoryInterface|MockObject */
        $factory = $this->createMock(FactoryInterface::class);

        $this->pcntlMock->expects(self::once())->method('fork')->willReturn(1000);

        $master = new MasterProcess($factory, $handler);
        $worker = $master->fork(function () {});

        self::assertInstanceOf(WorkerControlInterface::class, $worker);
    }

    public function testForkSuccessThreadSide()
    {
        /* @var $handler SignalHandlerInterface|MockObject */
        $handler = $this->createMock(SignalHandlerInterface::class);

        /* @var $factory FactoryInterface|MockObject */
        $factory = $this->createMock(FactoryInterface::class);

        $thread = $this->createMock(WorkerProcessInterface::class);
        $thread->expects(self::once())->method('setPID');
        $thread->expects(self::once())->method('work');
        $thread->expects(self::once())->method('exit');

        $factory->expects(self::once())->method('createWorkerProcess')->willReturn($thread);

        $this->pcntlMock->expects(self::once())->method('fork')->willReturn(0);

        $master = new MasterProcess($factory, $handler);
        $master->fork(function () {});
    }

    public function testWait()
    {
        /* @var $handler SignalHandlerInterface|MockObject */
        $handler = $this->createMock(SignalHandlerInterface::class);

        /* @var $factory FactoryInterface|MockObject */
        $factory = $this->createMock(FactoryInterface::class);

        $this->pcntlMock->expects(self::any())->method('setSignalHandler')->willReturn(true);
        $this->pcntlMock->expects(self::once())->method('fork')->willReturn(1000);
        $this->pcntlMock->expects(self::exactly(2))->method('waitPID')->willReturnOnConsecutiveCalls(1000, 0);

        $master = new MasterProcess($factory, $handler);

        $handler->expects(self::once())->method('dispatch')->willReturnCallback(function () use ($master) {
            $master->onSignalFromChild();
        });

        $master->fork(function () {});
        $master->wait();
    }

    public function testKill()
    {
        /* @var $handler SignalHandlerInterface|MockObject */
        $handler = $this->createMock(SignalHandlerInterface::class);

        /* @var $worker WorkerControlInterface|MockObject */
        $worker = $this->createMock(WorkerControlInterface::class);

        /* @var $factory FactoryInterface|MockObject */
        $factory = $this->createMock(FactoryInterface::class);
        $factory->expects(self::once())->method('createWorkerControl')->willReturn($worker);

        $this->pcntlMock->expects(self::any())->method('setSignalHandler')->willReturn(true);
        $this->pcntlMock->expects(self::once())->method('fork')->willReturn(1000);

        $master = new MasterProcess($factory, $handler);

        $worker = $master->fork(function () {});
        $worker->expects(self::once())->method('kill');

        $master->kill();
    }

    public function testGetChildren()
    {
        /* @var $handler SignalHandlerInterface|MockObject */
        $handler = $this->createMock(SignalHandlerInterface::class);

        /* @var $worker WorkerControlInterface|MockObject */
        $worker = $this->createMock(WorkerControlInterface::class);
        $worker->method('getAlias')->willReturn('foo');

        /* @var $factory FactoryInterface|MockObject */
        $factory = $this->createMock(FactoryInterface::class);
        $factory->expects(self::once())->method('createWorkerControl')->willReturn($worker);

        $pid = 1000;

        $this->pcntlMock->expects(self::any())->method('setSignalHandler')->willReturn(true);
        $this->pcntlMock->expects(self::once())->method('fork')->willReturn($pid);

        $master = new MasterProcess($factory, $handler);

        $worker = $master->fork(function () {});
        $worker->setAlias('foo');

        self::assertSame([$pid => $worker], $master->getChildren());
        self::assertSame([$pid => $worker], $master->getChildren('foo'));
    }
}
