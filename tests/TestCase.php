<?php

namespace XHGui\Test;

use XHGui\Saver\SaverInterface;
use XHGui\ServiceContainer;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * Load a fixture into the database.
     */
    protected function loadFixture(SaverInterface $saver, string $fileName = 'results.json'): void
    {
        $file = __DIR__ . '/fixtures/' . $fileName;
        $data = json_decode(file_get_contents($file), true);
        foreach ($data as $record) {
            $saver->save($record, $record['_id'] ?? null);
        }
    }

    protected function skipIfPdo($details = null): void
    {
        $saveHandler = ServiceContainer::instance()['config']['save.handler'];

        if ($saveHandler !== 'pdo') {
            return;
        }

        $message = 'PDO support is not complete';
        if ($details) {
            $message .= ': ' . $details;
        }
        $this->markTestIncomplete($message);
    }
}
