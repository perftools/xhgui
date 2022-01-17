<?php

use XHGui\Application;

require dirname(__DIR__) . '/vendor/autoload.php';

session_start();

$app = new Application();
$app->run();
