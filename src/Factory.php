<?php

namespace PE\Component\Cronos\Process;

class Factory implements FactoryInterface
{
    /**
     * @inheritDoc
     */
    public function createSignalHandler(): SignalHandlerInterface
    {
        return new SignalHandler();
    }

    /**
     * @inheritDoc
     */
    public function createMasterProcess(): MasterProcessInterface
    {
        return new MasterProcess($this, $this->createSignalHandler());
    }

    /**
     * @inheritDoc
     */
    public function createWorkerControl(): WorkerControlInterface
    {
        return new WorkerControl();
    }

    /**
     * @inheritDoc
     */
    public function createWorkerProcess(callable $callable): WorkerProcessInterface
    {
        return new WorkerProcess($callable, $this->createSignalHandler());
    }
}
