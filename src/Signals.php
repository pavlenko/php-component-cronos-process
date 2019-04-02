<?php

namespace PE\Component\Cronos\Process;

class Signals
{
    /**
     * @var callable[]
     */
    private $handlers;

    /**
     * @var \SplQueue
     */
    private $queue;

    /**
     * Signals constructor.
     */
    public function __construct()
    {
        $this->handlers = [];

        $this->queue = new \SplQueue();
        $this->queue->setIteratorMode(\SplQueue::IT_MODE_DELETE);
    }

    /**
     * Register given callable with signal.
     *
     * @param int      $signal The signal code.
     * @param callback $handler The signal handler.
     *
     * @throws \RuntimeException If could not register handler
     */
    public function registerHandler(int $signal, callable $handler)
    {
        if (!isset($this->handlers[$signal])) {
            $this->handlers[$signal] = [];

            if (!PCNTL::getInstance()->setSignalHandler($signal, [$this, 'enqueue'])) {
                throw new \RuntimeException(sprintf('Could not register signal %d', $signal));
            };
        };

        if (!in_array($handler, $this->handlers[$signal], true)) {
            $this->handlers[$signal][] = $handler;
        }
    }

    /**
     * Enqueue signal to dispatch in feature.
     *
     * @param int $signal The signal code.
     *
     * @return void
     */
    public function enqueue($signal)
    {
        $this->queue->enqueue($signal);
    }

    /**
     * Execute all registered handlers.
     */
    public function dispatch()
    {
        PCNTL::getInstance()->dispatch();

        foreach ($this->queue as $signal) {
            foreach ($this->handlers[$signal] as $callable) {
                call_user_func($callable, $signal);
            }
        }
    }
}
