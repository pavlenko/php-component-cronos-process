<?php

namespace PE\Component\Cronos\Process;

class Daemon
{
    /**
     * @var string
     */
    private $pidFile;

    /**
     * @var FactoryInterface
     */
    private $factory;

    /**
     * @param string                $pidFile
     * @param FactoryInterface|null $factory
     */
    public function __construct(string $pidFile, FactoryInterface $factory = null)
    {
        $this->pidFile = $pidFile;
        $this->factory = $factory ?: new Factory();
    }

    public function createPIDFile($pid): void
    {
        file_put_contents($this->pidFile, $pid);
    }

    public function removePIDFile(): void
    {
        @unlink($this->pidFile);
    }

    /**
     * Run process as a daemon
     *
     * @param callable $callable
     */
    public function fork(callable $callable): void
    {
        if (is_file($this->pidFile)) {
            $pid = (int) file_get_contents($this->pidFile);

            if (POSIX::getInstance()->kill($pid, 0)) {
                return;
            }

            $this->removePIDFile();
        }

        $pid = PCNTL::getInstance()->fork();

        if (-1 === $pid) {
            throw new \RuntimeException('Failure to fork process');
        }

        $worker = $this->factory->createWorkerControl();
        $thread = $this->factory->createWorkerProcess($callable);

        if ($pid) {
            $this->createPIDFile($pid);

            $worker->setPid($pid);
            $worker->exit(0);
            return;
        }

        POSIX::getInstance()->setSessionID();

        $thread->setPid((int) file_get_contents($this->pidFile));
        $thread->work();

        $this->removePIDFile();
    }

    /**
     * Kill daemon with specific signal
     *
     * @param int $signal
     */
    public function kill(int $signal = PCNTL::SIGTERM): void
    {
        if (is_file($this->pidFile)) {
            $pid = file_get_contents($this->pidFile);

            if (!POSIX::getInstance()->kill($pid, 0)) {
                @unlink($this->pidFile);
                return;
            }

            if (!POSIX::getInstance()->kill($pid, $signal)) {
                throw new \RuntimeException('Error kill daemon with PID = ' . $pid);
            }
        }
    }
}
