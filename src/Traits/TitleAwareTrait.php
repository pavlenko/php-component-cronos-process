<?php

namespace PE\Component\Cronos\Process\Traits;

/**
 * @codeCoverageIgnore
 */
trait TitleAwareTrait
{
    /**
     * @var string
     */
    private $title;

    /**
     * Get process title if possible
     *
     * @return string|null
     */
    public function getTitle(): ?string
    {
        if (null !== $this->title) {
            return $this->title;
        }

        if (function_exists('cli_get_process_title')) {
            return cli_get_process_title();
        }

        return null;
    }

    /**
     * Set process title if possible
     *
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;

        if (function_exists('cli_set_process_title')) {
            cli_set_process_title($title);
        } else if(function_exists('setproctitle')) {
            setproctitle($title);
        }
    }
}
