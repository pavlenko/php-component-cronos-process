<?php

namespace PE\Component\Cronos\Process\Tests;

use PE\Component\Cronos\Process\PCNTL;
use PE\Component\Cronos\Process\SignalHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SignalHandlerTest extends TestCase
{
    private $pcntlOriginal;
    private $pcntlMock;
    private $handler;

    protected function setUp()
    {
        $this->pcntlOriginal = PCNTL::getInstance();
        $this->pcntlMock     = $this->createMock(PCNTL::class);

        $this->handler = new SignalHandler();

        PCNTL::setInstance($this->pcntlMock);
    }

    protected function tearDown()
    {
        PCNTL::setInstance($this->pcntlOriginal);
    }

    public function testRegisterHandlerFailure(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->pcntlMock
            ->expects(static::once())
            ->method('setSignalHandler')
            ->with(PCNTL::SIGTERM, [$this->handler, 'enqueue'])
            ->willReturn(false);

        $this->handler->attachListener(PCNTL::SIGTERM, function(){});
    }

    public function testRegisterHandlerSuccess(): void
    {
        $this->pcntlMock
            ->expects(static::once())
            ->method('setSignalHandler')
            ->with(PCNTL::SIGTERM, [$this->handler, 'enqueue'])
            ->willReturn(true);

        $this->handler->attachListener(PCNTL::SIGTERM, function(){});
    }

    public function testDispatch()
    {
        $this->pcntlMock
            ->expects(static::once())
            ->method('setSignalHandler')
            ->with(PCNTL::SIGTERM, [$this->handler, 'enqueue'])
            ->willReturn(true);

        $this->pcntlMock
            ->expects(static::once())
            ->method('dispatch')
            ->willReturnCallback(function () {
                $this->handler->enqueue(PCNTL::SIGTERM);
                return true;
            });

        /* @var $listener callable|MockObject */
        $listener = $this->getMockBuilder(\stdClass::class)->setMethods(['__invoke'])->getMock();
        $listener->expects(static::once())->method('__invoke');

        $this->handler->attachListener(PCNTL::SIGTERM, $listener);
        $this->handler->dispatch();
    }
}
