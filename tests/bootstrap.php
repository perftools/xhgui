<?php
/**
 * Bootstrap for xhgui test suite.
 */

use XHGui\ServiceContainer;

require dirname(__DIR__) . '/src/bootstrap.php';

$di = ServiceContainer::instance();

// Use a test database.
$config = $di['config'];
$config['db.db'] = 'test_' . $config['db.db'];
$di['config'] = $config;

// Clean up globals.
unset($di, $config);
