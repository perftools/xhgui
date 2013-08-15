<?php
/**
 * Bootstrap for xhgui test suite.
 */
require dirname(__DIR__) . '/src/bootstrap.php';

// Use a test database.
$dbname = Xhgui_Config::read('db.db');
Xhgui_Config::write('db.db', 'test_' . $dbname);
