<?php
require dirname(__DIR__) . '/src/bootstrap.php';

$di = new Xhgui_ServiceContainer();


$app = $di['app'];

$app->hook('slim.before.dispatch', function() use ($di, $app) {
    $site = $app->router()->getCurrentRoute()->getParam('site');
    $di['sites']->setCurrent($site);
});

require XHGUI_ROOT_DIR . '/src/routes.php';

$app->run();
