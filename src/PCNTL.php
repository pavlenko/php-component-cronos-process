<?php

namespace PE\Component\Cronos\Process;

/**
 * @codeCoverageIgnore
 */
class PCNTL
{
    public const SIGINT  = 2;
    public const SIGKILL = 9;
    public const SIGTERM = 15;
    public const SIGCHLD = 17;

    public const WNOHANG    = 1;
    public const WUNTRACED  = 2;
    public const WCONTINUED = 16;

    public const SIG_DFL = 0; // Pass to setSignalHandler as $handler for use default handler
    public const SIG_IGN = 1; // Pass to setSignalHandler as $handler for use ignore handler

    /**
     * @var PCNTL
     */
    private static $instance;

    /**
     * @return PCNTL
     */
    public static function getInstance(): PCNTL
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @param PCNTL $instance
     */
    public static function setInstance(PCNTL $instance): void
    {
        self::$instance = $instance;
    }

    /**
     * Disallow new instances
     */
    private function __construct()
    {}

    /**
     * @return bool
     */
    public function dispatch(): bool
    {
        return \pcntl_signal_dispatch();
    }

    /**
     * @return int
     */
    public function fork(): int
    {
        return \pcntl_fork();
    }

    /**
     * @param int $pid
     * @param int $status
     * @param int $options
     *
     * @return int
     */
    public function waitPID(int $pid, int &$status, int $options = 0): int
    {
        return \pcntl_waitpid($pid, $status, $options);
    }

    /**
     * @return bool
     */
    public function getAsyncSignalsEnabled(): bool
    {
        return \pcntl_async_signals();
    }

    /**
     * @param bool $flag
     */
    public function setAsyncSignalsEnabled(bool $flag): void
    {
        \pcntl_async_signals($flag);
    }

    /**
     * @param int $signal
     * @param int|callable $handler
     * @param bool         $restartSYSCalls
     *
     * @return bool
     */
    public function setSignalHandler(int $signal, $handler, bool $restartSYSCalls = true): bool
    {
        return \pcntl_signal($signal, $handler, $restartSYSCalls);
    }
}
