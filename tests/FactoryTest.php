<?php

namespace PE\Component\Cronos\Process\Tests;

use PE\Component\Cronos\Process\Factory;
use PE\Component\Cronos\Process\MasterProcessInterface;
use PE\Component\Cronos\Process\PCNTL;
use PE\Component\Cronos\Process\SignalHandlerInterface;
use PE\Component\Cronos\Process\WorkerProcessInterface;
use PE\Component\Cronos\Process\WorkerControlInterface;
use PHPUnit\Framework\TestCase;

class FactoryTest extends TestCase
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

    public function testCreateSignalHandler()
    {
        self::assertInstanceOf(SignalHandlerInterface::class, (new Factory())->createSignalHandler());
    }

    public function testCreateMasterProcess()
    {
        $this->pcntlMock->expects(self::any())->method('setSignalHandler')->willReturn(true);
        self::assertInstanceOf(MasterProcessInterface::class, (new Factory())->createMasterProcess());
    }

    public function testCreateWorkerControl()
    {
        self::assertInstanceOf(WorkerControlInterface::class, (new Factory())->createWorkerControl());
    }

    public function testCreateWorkerProcess()
    {
        self::assertInstanceOf(WorkerProcessInterface::class, (new Factory())->createWorkerProcess(function () {}));
    }
}
