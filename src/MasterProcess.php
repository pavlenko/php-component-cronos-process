<?php

namespace PE\Component\Cronos\Process;

use PE\Component\Cronos\Dispatcher\EventDispatcherTrait;
use PE\Component\Cronos\Process\Traits\TitleAwareTrait;

class MasterProcess extends ProcessBase implements MasterProcessInterface
{
    use EventDispatcherTrait;
    use TitleAwareTrait;

    /**
     * @var bool
     */
    private $shouldTerminate = false;

    /**
     * @var FactoryInterface
     */
    private $factory;

    /**
     * @var SignalHandlerInterface
     */
    private $signalHandler;

    /**
     * @var WorkerControlInterface[]
     */
    private $children = [];

    /**
     * @param FactoryInterface       $factory
     * @param SignalHandlerInterface $signalHandler
     */
    public function __construct(FactoryInterface $factory, SignalHandlerInterface $signalHandler)
    {
        $this->factory = $factory;

        $this->signalHandler = $signalHandler;
        $this->signalHandler->attachListener(PCNTL::SIGTERM, [$this, 'kill']);
        $this->signalHandler->attachListener(PCNTL::SIGINT, [$this, 'kill']);
        $this->signalHandler->attachListener(PCNTL::SIGCHLD, [$this, 'onSignalFromChild']);
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
     * @inheritDoc
     */
    public function getChildren(string $alias = null): array
    {
        return array_filter($this->children, function (WorkerControlInterface $worker) use ($alias) {
            return empty($alias) || ($worker->getAlias() === $alias);
        });
    }

    /**
     * @internal
     */
    public function onSignalFromChild()
    {
        $status = 0;
        while (($pid = PCNTL::getInstance()->waitPID(-1, $status, PCNTL::WNOHANG)) > 0) {
            $child = $this->children[$pid];

            unset($this->children[$pid]);

            $this->trigger(self::EVENT_WORKER_EXIT, $child);
        }

        gc_collect_cycles();
    }

    /**
     * @inheritDoc
     */
    public function fork(callable $callable): ?WorkerControlInterface
    {
        $worker = $this->factory->createWorkerControl();
        $thread = $this->factory->createWorkerProcess($callable);

        $pid = PCNTL::getInstance()->fork();

        if (-1 === $pid) {
            throw new \RuntimeException('Failure to fork process');
        }

        $this->trigger(self::EVENT_MASTER_FORK, $worker);

        if ($pid) {
            $this->children[$pid] = $worker;

            $worker->setPid($pid);

            return $worker;
        }

        $thread->setPid(getmypid());
        $thread->work();
        $thread->exit();
        return null;
    }

    public function wait()
    {
        while (count($this->children) > 0) {
            $this->signalHandler->dispatch();
            usleep(100000);
        }
    }

    /**
     * @inheritDoc
     */
    public function kill(int $signal = PCNTL::SIGTERM): void
    {
        $this->shouldTerminate = true;

        foreach ($this->children as $worker) {
            $worker->kill($signal);
        }

        $this->trigger(self::EVENT_MASTER_EXIT);
    }
}
