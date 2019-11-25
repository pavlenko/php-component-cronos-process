<?php

namespace PE\Component\Cronos\Process;

use PE\Component\Cronos\Process\Traits\PIDAwareTrait;
use PE\Component\Cronos\Process\Traits\TitleAwareTrait;

class WorkerProcess extends ProcessBase implements WorkerProcessInterface
{
    use PIDAwareTrait;
    use TitleAwareTrait;

    /**
     * @var bool
     */
    private $shouldTerminate = false;

    /**
     * @var callable
     */
    private $callable;

    /**
     * @var SignalHandlerInterface
     */
    private $signalHandler;

    /**
     * @param callable               $callable
     * @param SignalHandlerInterface $signalHandler
     */
    public function __construct(callable $callable, SignalHandlerInterface $signalHandler)
    {
        $this->callable      = $callable;
        $this->signalHandler = $signalHandler;
    }

    /**
     * @inheritDoc
     */
    public function isShouldTerminate(): bool
    {
        $this->signalHandler->dispatch();
        return $this->shouldTerminate;
    }

    /**
     * @internal
     */
    public function onSignalTerminate()
    {
        $this->shouldTerminate = true;
    }

    /**
     * @inheritDoc
     */
    public function work(): void
    {
        $this->signalHandler->attachListener(PCNTL::SIGTERM, [$this, 'onSignalTerminate']);
        $this->signalHandler->attachListener(PCNTL::SIGINT, [$this, 'onSignalTerminate']);

        PCNTL::getInstance()->setAsyncSignalsEnabled(true);

        call_user_func($this->callable, $this);
    }

    /**
     * @inheritDoc
     * @codeCoverageIgnore
     */
    public function exit(int $code = 0): void
    {
        exit($code);
    }
}
