<?php

namespace XHGui\Test;

use XHGui\Saver\SaverInterface;
use XHGui\ServiceContainer;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * Load a fixture into the database.
     */
    protected function importFixture(SaverInterface $saver, string $fileName = 'normalized.json'): void
    {
        foreach ($this->loadFixture($fileName) as $record) {
            $saver->save($record, $record['_id'] ?? null);
        }
    }

    protected function loadFixture(string $fileName): array
    {
        $file = __DIR__ . '/fixtures/' . $fileName;
        $this->assertFileExists($file);
        $data = json_decode(file_get_contents($file), true);
        $this->assertNotEmpty($data);

        return $data;
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
