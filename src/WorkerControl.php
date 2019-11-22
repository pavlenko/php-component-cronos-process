<?php

namespace PE\Component\Cronos\Process;

use PE\Component\Cronos\Process\Traits\PIDAwareTrait;

class WorkerControl implements WorkerControlInterface
{
    use PIDAwareTrait;

    /**
     * @var string
     */
    private $alias;

    /**
     * @inheritDoc
     */
    public function getAlias(): string
    {
        return (string) $this->alias;
    }

    /**
     * @inheritDoc
     */
    public function setAlias(string $alias): void
    {
        $this->alias = $alias;
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
     * @codeCoverageIgnore
     */
    public function exit(int $code = 0): void
    {
        exit($code);
    }
}
