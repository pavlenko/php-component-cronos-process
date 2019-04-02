<?php

namespace PE\Component\Cronos\Process;

class Process
{
    /**
     * @var int
     */
    private $pid = 0;

    /**
     * @var callable
     */
    private $callable;

    /**
     * @var string
     */
    private $title;

    /**
     * @var bool
     */
    private $runned = false;

    /**
     * @var Process[]
     */
    private $children = [];

    /**
     * @var Signals|null
     */
    private $signals;

    /**
     * @var bool
     */
    private $shouldTerminate = false;

    /**
     * Sets the process title.
     *
     * @param string $title
     *
     * @codeCoverageIgnore
     */
    public function setProcessTitle(string $title): void
    {
        if ($this->runned) {
            \cli_set_process_title($this->title = $title);
        } else {
            $this->title = $title;
        }
    }

    /**
     * Returns the current process title.
     *
     * @return string
     *
     * @codeCoverageIgnore
     */
    public function getProcessTitle(): string
    {
        return (string) ($this->title ?: \cli_get_process_title());
    }

    /**
     * Get current process pid
     *
     * @return int
     *
     * @codeCoverageIgnore
     */
    public function getPID(): int
    {
        return $this->pid;
    }

    /**
     * Set current process pid
     *
     * @param int $pid
     *
     * @codeCoverageIgnore
     */
    public function setPID(int $pid): void
    {
        $this->pid = $pid;
    }

    /**
     * @return callable|null
     */
    public function getCallable(): ?callable
    {
        return $this->callable;
    }

    /**
     * @param callable $callable
     */
    public function setCallable(callable $callable): void
    {
        $this->callable = $callable;
    }

    /**
     * @return Process[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * @return Signals
     */
    public function getSignals(): Signals
    {
        if ($this->signals === null) {
            $this->signals = new Signals();
        }

        return $this->signals;
    }

    /**
     * @param Signals $signals
     */
    public function setSignals(Signals $signals): void
    {
        $this->signals = $signals;
    }

    /**
     * @return bool
     */
    public function isShouldTerminate(): bool
    {
        return $this->shouldTerminate;
    }

    /**
     * @internal
     */
    public function onSignalTerminate(): void
    {
        $this->shouldTerminate = true;

        foreach ($this->children as $process) {
            $process->kill();
        }
    }

    /**
     * @internal
     */
    public function onSignalFromChild(): void
    {
        $status = 0;
        while (($pid = PCNTL::getInstance()->waitPID(-1, $status, PCNTL::WNOHANG)) > 0) {
            unset($this->children[$pid]);
        }

        gc_collect_cycles();
    }

    /**
     * Execute process logic
     */
    public function run(): void
    {
        if (!$this->callable) {
            throw new \RuntimeException('Cannot run process without callable');
        }

        $this->runned = true;

        if ($this->title) {
            $this->setProcessTitle($this->title);
        }

        PCNTL::getInstance()->setAsyncSignalsEnabled(true);

        $signals = $this->getSignals();
        $signals->registerHandler(PCNTL::SIGTERM, [$this, 'onSignalTerminate']);

        call_user_func($this->callable, $this);
    }

    /**
     * @param Process $process
     */
    public function fork(Process $process): void
    {
        if (!$process->getCallable()) {
            throw new \RuntimeException('Cannot fork process without callable');
        }

        $this->runned = true;

        if ($this->title) {
            $this->setProcessTitle($this->title);
        }

        $signals = $this->getSignals();
        $signals->registerHandler(PCNTL::SIGTERM, [$this, 'onSignalTerminate']);
        $signals->registerHandler(PCNTL::SIGCHLD, [$this, 'onSignalFromChild']);

        $pid = PCNTL::getInstance()->fork();

        if (-1 === $pid) {
            throw new \RuntimeException('Failure to fork process');
        }

        if ($pid) {
            $this->children[$pid] = $process;

            $process->setPid($pid);
            return;
        }

        $process->setPid(getmypid());
        $process->run();
        $process->exit(0);
    }

    /**
     * @param int|string $status
     *
     * @codeCoverageIgnore
     */
    public function exit($status = ''): void
    {
        exit($status);
    }

    /**
     * Wait for children processes is completed
     */
    public function wait(): void
    {
        while (count($this->children) > 0) {
            $this->dispatch();
            usleep(100000);
        }
    }

    /**
     * @inheritDoc
     */
    public function kill(int $signal = PCNTL::SIGTERM): void
    {
        POSIX::getInstance()->kill($this->pid, $signal);
    }

    /**
     * @inheritDoc
     */
    public function dispatch(): void
    {
        $this->getSignals()->dispatch();
    }
}
