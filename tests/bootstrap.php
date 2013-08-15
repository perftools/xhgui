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
