<?php

namespace XHGui\Test;

use XHGui\Saver\SaverInterface;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * Load a fixture into the database.
     */
    protected function loadFixture(SaverInterface $saver, $file)
    {
        $data = json_decode(file_get_contents($file), true);
        foreach ($data as $record) {
            $saver->save($record, $record['_id'] ?? null);
        }
    }
}
