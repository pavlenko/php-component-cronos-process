<?php

namespace PE\Component\Cronos\Process;

/**
 * @codeCoverageIgnore
 */
class POSIX
{
    /**
     * @var POSIX
     */
    private static $instance;

    /**
     * @return POSIX
     */
    public static function getInstance(): POSIX
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @param POSIX $instance
     */
    public static function setInstance(POSIX $instance): void
    {
        self::$instance = $instance;
    }

    /**
     * Disallow new instances
     */
    private function __construct()
    {}

    /**
     * @param int $pid
     * @param int $signal
     *
     * @return bool
     */
    public function kill(int $pid, int $signal): bool
    {
        return \posix_kill($pid, $signal);
    }

    /**
     * @return int
     */
    public function setSessionID(): int
    {
        return \posix_setsid();
    }
}
