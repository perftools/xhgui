<?php

use XHGui\ServiceContainer;

require dirname(__DIR__) . '/src/bootstrap.php';

$di = new ServiceContainer();

$app = $di['app'];

require XHGUI_ROOT_DIR . '/src/routes.php';

$app->run();
