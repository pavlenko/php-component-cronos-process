<?php

namespace PE\Component\Cronos\Process;

use PE\Component\Cronos\Dispatcher\EventDispatcherTrait;

class SignalHandler implements SignalHandlerInterface
{
    use EventDispatcherTrait {
        attachListener as private _attachListener;
    }

    /**
     * @var \SplQueue
     */
    private $queue;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->queue = new \SplQueue();
        $this->queue->setIteratorMode(\SplQueue::IT_MODE_DELETE);
    }

    /**
     * @inheritDoc
     */
    public function attachListener(string $event, callable $listener, int $priority = 0): bool
    {
        if (empty($this->listeners[$event])) {
            if (!PCNTL::getInstance()->setSignalHandler($event, [$this, 'enqueue'])) {
                throw new \RuntimeException(sprintf('Could not register signal %d', $event));
            };
        }

        return $this->_attachListener($event, $listener, $priority);
    }

    /**
     * Enqueue signal to dispatch in feature.
     *
     * @param int $signal The signal code.
     *
     * @internal
     */
    public function enqueue($signal): void
    {
        $this->queue->enqueue($signal);
    }

    /**
     * @inheritDoc
     */
    public function dispatch(): void
    {
        PCNTL::getInstance()->dispatch();

        foreach ($this->queue as $signal) {
            $this->trigger($signal, $signal);
        }
    }
}
