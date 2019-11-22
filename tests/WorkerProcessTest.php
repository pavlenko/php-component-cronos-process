<?php

namespace PE\Component\Cronos\Process\Tests;

use PE\Component\Cronos\Process\PCNTL;
use PE\Component\Cronos\Process\SignalHandlerInterface;
use PE\Component\Cronos\Process\WorkerProcess;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WorkerProcessTest extends TestCase
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

    public function testIsShouldTerminateReturnsFalse()
    {
        /* @var $handler SignalHandlerInterface|MockObject */
        $handler = $this->createMock(SignalHandlerInterface::class);

        $worker = new WorkerProcess(function() use (&$executed) { $executed = true; }, $handler);

        self::assertFalse($worker->isShouldTerminate());
    }

    public function testIsShouldTerminateReturnsTrue()
    {
        /* @var $handler SignalHandlerInterface|MockObject */
        $handler = $this->createMock(SignalHandlerInterface::class);

        $worker = new WorkerProcess(function() use (&$executed) { $executed = true; }, $handler);
        $worker->onSignalTerminate();

        self::assertTrue($worker->isShouldTerminate());
    }

    public function testWork()
    {
        /* @var $handler SignalHandlerInterface|MockObject */
        $handler = $this->createMock(SignalHandlerInterface::class);
        $handler->expects(self::exactly(2))->method('attachListener');

        $this->pcntlMock->expects(self::once())->method('setAsyncSignalsEnabled')->with(true);

        $executed = false;

        $worker = new WorkerProcess(function() use (&$executed) { $executed = true; }, $handler);
        $worker->work();

        self::assertTrue($executed);
    }
}
