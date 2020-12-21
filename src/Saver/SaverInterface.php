<?php

namespace XHGui\Saver;

interface SaverInterface
{
    /**
     * Returns id of the saved profile
     */
    public function save(array $data, string $id = null): string;
}
