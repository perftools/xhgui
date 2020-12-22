<?php
/**
 * Bootstrap for xhgui test suite.
 */

use XHGui\ServiceContainer;

require __DIR__ . '/../vendor/autoload.php';

$di = ServiceContainer::instance();

// Use a test databases
$di['mongodb.database'] = 'test_xhgui';
// TODO: do the same for PDO. currently PDO uses DSN syntax and has too many variations

// Clean up globals.
unset($di, $config);
