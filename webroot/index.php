<?php
require dirname(__DIR__) . '/src/bootstrap.php';

$di = new Xhgui_ServiceContainer();

$app = $di['app'];

require XHGUI_ROOT_DIR . '/src/app/hooks.php';
require XHGUI_ROOT_DIR . '/src/app/controllers/runs.php';
require XHGUI_ROOT_DIR . '/src/app/controllers/error.php';
require XHGUI_ROOT_DIR . '/src/app/routes.php';

$app->run();
