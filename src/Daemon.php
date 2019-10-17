<?php

namespace PE\Component\Cronos\Process;

class Daemon
{
    /**
     * @var string
     */
    private $pidFile;

    /**
     * @param string $pidFile
     */
    public function __construct(string $pidFile)
    {
        $this->pidFile = $pidFile;
    }

    public function createPIDFile($pid)
    {
        file_put_contents($this->pidFile, $pid);
    }

    public function removePIDFile()
    {
        @unlink($this->pidFile);
    }

    /**
     * Run process as a daemon
     *
     * @param Process $process
     */
    public function fork(Process $process): void
    {
        if (!$process->getCallable()) {
            throw new \RuntimeException('Cannot fork process without callable');
        }

        if (is_file($this->pidFile)) {
            $pid = (int) file_get_contents($this->pidFile);

            if (POSIX::getInstance()->kill($pid, 0)) {
                return;
            }

            $this->removePIDFile();
            //unlink($this->pidFile);
        }

        $pid = PCNTL::getInstance()->fork();

        if (-1 === $pid) {
            throw new \RuntimeException('Failure to fork process');
        }

        if ($pid) {
            $this->createPIDFile($pid);
            //file_put_contents($this->pidFile, $pid);

            $process->setPid($pid);
            $process->exit(0);
            return;
        }

        POSIX::getInstance()->setSessionID();

        $process->setPid((int) file_get_contents($this->pidFile));
        $process->run();

        $this->removePIDFile();
        //@unlink($this->pidFile);
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
