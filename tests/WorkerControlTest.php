<?php

namespace PE\Component\Cronos\Process\Tests;

use PE\Component\Cronos\Process\PCNTL;
use PE\Component\Cronos\Process\POSIX;
use PE\Component\Cronos\Process\WorkerControl;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WorkerControlTest extends TestCase
{
    /**
     * @var POSIX
     */
    private $posixOriginal;

    /**
     * @var POSIX|MockObject
     */
    private $posixMock;

    protected function setUp()
    {
        $this->posixOriginal = POSIX::getInstance();
        $this->posixMock     = $this->createMock(POSIX::class);

        POSIX::setInstance($this->posixMock);
    }

    protected function tearDown()
    {
        POSIX::setInstance($this->posixOriginal);
    }

    public function testAlias()
    {
        $control = new WorkerControl();

        self::assertSame('', $control->getAlias());

        $control->setAlias('foo');

        self::assertSame('foo', $control->getAlias());
    }

    public function testKillDefault()
    {
        $this->posixMock->expects(self::once())->method('kill')->with(1000, PCNTL::SIGTERM);

        $control = new WorkerControl();
        $control->setPID(1000);
        $control->kill();
    }

    public function testKillCustom()
    {
        $this->posixMock->expects(self::once())->method('kill')->with(1000, PCNTL::SIGINT);

        $control = new WorkerControl();
        $control->setPID(1000);
        $control->kill(PCNTL::SIGINT);
    }
}
