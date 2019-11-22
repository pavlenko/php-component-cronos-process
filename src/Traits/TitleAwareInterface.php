<?php

namespace PE\Component\Cronos\Process\Traits;

interface TitleAwareInterface
{
    /**
     * Get process title if possible
     *
     * @return string|null
     */
    public function getTitle(): ?string;

    /**
     * Set process title if possible
     *
     * @param string $title
     */
    public function setTitle(string $title): void;
}
