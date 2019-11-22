<?php

namespace PE\Component\Cronos\Process;

interface FactoryInterface
{
    /**
     * @return SignalHandlerInterface
     */
    public function createSignalHandler(): SignalHandlerInterface;

    /**
     * @return MasterProcessInterface
     */
    public function createMasterProcess(): MasterProcessInterface;

    /**
     * @return WorkerControlInterface
     */
    public function createWorkerControl(): WorkerControlInterface;

    /**
     * @param callable $callable
     *
     * @return WorkerProcessInterface
     */
    public function createWorkerProcess(callable $callable): WorkerProcessInterface;
}
