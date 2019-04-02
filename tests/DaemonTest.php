<?php

namespace PE\Component\Cronos\Process\Tests;

use PE\Component\Cronos\Process\Daemon;
use PE\Component\Cronos\Process\PCNTL;
use PE\Component\Cronos\Process\POSIX;
use PE\Component\Cronos\Process\Process;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DaemonTest extends TestCase
{
    /**
     * @var string
     */
    private $pidFile = __DIR__ . '/cronos.pid';

    /**
     * @var POSIX|MockObject
     */
    private $posix;

    /**
     * @var PCNTL|MockObject
     */
    private $pcntl;

    /**
     * @var Daemon
     */
    private $daemon;

    protected function setUp()
    {
        $this->posix  = $this->createMock(POSIX::class);
        $this->pcntl  = $this->createMock(PCNTL::class);
        $this->daemon = new Daemon($this->pidFile);

        POSIX::setInstance($this->posix);
        PCNTL::setInstance($this->pcntl);
    }

    protected function tearDown()
    {
        @unlink($this->pidFile);
    }

    public function testForkErrorCallable(): void
    {
        $this->expectException(\RuntimeException::class);

        /* @var $process Process|MockObject */
        $process = $this->createMock(Process::class);

        $this->daemon->fork($process);
    }

    public function testForkErrorPCNTL(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->pcntl->expects(self::once())->method('fork')->willReturn(-1);

        /* @var $process Process|MockObject */
        $process = $this->createMock(Process::class);
        $process->expects(self::once())->method('getCallable')->willReturn(function () {});

        $this->daemon->fork($process);
    }

    public function testForkSkipAlreadyRunning(): void
    {
        /* @var $process Process|MockObject */
        $process = $this->createMock(Process::class);
        $process->expects(self::once())->method('getCallable')->willReturn(function () {});

        file_put_contents($this->pidFile, '1000');

        $this->posix->expects(self::once())->method('kill')->with(1000, 0)->willReturn(true);
        $this->pcntl->expects(self::never())->method('fork');

        $this->daemon->fork($process);
    }

    public function testForkUnlinkDefunctFile(): void
    {
        /* @var $process Process|MockObject */
        $process = $this->createMock(Process::class);
        $process->expects(self::once())->method('getCallable')->willReturn(function () {});

        file_put_contents($this->pidFile, '1000');

        $this->posix->expects(self::once())->method('kill')->with(1000, 0)->willReturn(false);
        $this->pcntl->expects(self::once())->method('fork')->willReturnCallback(function () {
            self::assertFileNotExists($this->pidFile);
            return -1;
        });

        // We need an exception for prevent execution code after
        $this->expectException(\RuntimeException::class);

        $this->daemon->fork($process);
    }

    public function testForkParent(): void
    {
        /* @var $process Process|MockObject */
        $process = $this->createMock(Process::class);
        $process->expects(self::once())->method('getCallable')->willReturn(function () {});
        $process->expects(self::once())->method('setPID')->with(1000);
        $process->expects(self::once())->method('exit');

        $this->pcntl->expects(self::once())->method('fork')->willReturn(1000);

        $this->daemon->fork($process);

        self::assertStringEqualsFile($this->pidFile, '1000');
    }

    public function testForkChild(): void
    {
        /* @var $process Process|MockObject */
        $process = $this->createMock(Process::class);
        $process->expects(self::once())->method('getCallable')->willReturn(function () {});
        $process->expects(self::once())->method('setPID')->with(1000);
        $process->expects(self::once())->method('run');

        $this->posix->expects(self::once())->method('setSessionID');
        $this->pcntl->expects(self::once())->method('fork')->willReturnCallback(function () {
            file_put_contents($this->pidFile, '1000');
            return 0;
        });

        $this->daemon->fork($process);
    }

    public function testKillWithoutFile(): void
    {
        $this->posix->expects(self::never())->method('kill');
        $this->daemon->kill();
    }

    public function testKillWithNoProcess(): void
    {
        file_put_contents($this->pidFile, '1000');

        $this->posix->expects(self::once())->method('kill')->with(1000, 0)->willReturn(false);
        $this->daemon->kill();

        self::assertFileNotExists($this->pidFile);
    }

    public function testKillError(): void
    {
        $this->expectException(\RuntimeException::class);

        file_put_contents($this->pidFile, '1000');

        $this->posix
            ->expects(self::exactly(2))
            ->method('kill')
            ->withConsecutive([1000, 0], [1000, 15])
            ->willReturnOnConsecutiveCalls(true, false);

        $this->daemon->kill();
    }

    public function testKillSuccess(): void
    {
        file_put_contents($this->pidFile, '1000');

        $this->posix
            ->expects(self::exactly(2))
            ->method('kill')
            ->withConsecutive([1000, 0], [1000, 15])
            ->willReturnOnConsecutiveCalls(true, true);

        $this->daemon->kill();

        self::assertFileExists($this->pidFile);
    }
}
