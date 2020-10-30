<?php

namespace XHGui\Test;

use XHGui\Saver\SaverInterface;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * Load a fixture into the database.
     */
    protected function loadFixture(SaverInterface $saver, string $fileName = 'results.json')
    {
        $file = __DIR__ . '/fixtures/' . $fileName;
        $data = json_decode(file_get_contents($file), true);
        foreach ($data as $record) {
            $saver->save($record, $record['_id'] ?? null);
        }
    }

    protected function skipIfPdo($details = null)
    {
        $saveHandler = getenv('XHGUI_SAVE_HANDLER');
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
