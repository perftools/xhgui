<?php
require dirname(__DIR__) . '/src/bootstrap.php';

$di = new Xhgui_ServiceContainer();

if (false === ($di['sites']->hasCurrent())) {
}

$app = $di['app'];

require XHGUI_ROOT_DIR . '/src/routes.php';

$app->run();
