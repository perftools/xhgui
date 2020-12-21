<?php

use Slim\Slim as App;
use XHGui\ServiceContainer;

require dirname(__DIR__) . '/src/bootstrap.php';

$di = ServiceContainer::instance();
/** @var App $app */
$app = $di['app'];
$app->run();
