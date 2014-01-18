<?php
/**
 * Bootstrap for xhgui test suite.
 */
require dirname(__DIR__) . '/src/bootstrap.php';

$di = Xhgui_ServiceContainer::instance();

// Use a test database.
$config = $di['config'];
$config['db.db'] = 'test_' . $config['db.db'];
$di['config'] = $config;

// Clean up globals.
unset($di, $config);

/**
 * Load a fixture into the database.
 */
function loadFixture($profiles, $file) {
    $contents = file_get_contents($file);
    $data = json_decode($contents, true);
    foreach ($data as $record) {
        if (isset($record['meta']['request_time'])) {
            $time = strtotime($record['meta']['request_time']);
            $record['meta']['request_time'] = new MongoDate($time);
        }
        if (isset($record['_id'])) {
            $record['_id'] = new MongoId($record['_id']);
        }
        $profiles->insert($record);
    }
}
