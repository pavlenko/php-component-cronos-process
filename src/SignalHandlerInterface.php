<?php

namespace PE\Component\Cronos\Process;

use PE\Component\Cronos\Dispatcher\EventDispatcherInterface;

interface SignalHandlerInterface extends EventDispatcherInterface
{
    /**
     * Dispatch received signals
     */
    public function dispatch(): void;
}
