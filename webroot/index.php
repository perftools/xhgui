<?php
require dirname(__DIR__) . '/src/bootstrap.php';

$di = new Xhgui_ServiceContainer();


$app = $di['app'];

$app->hook('slim.before.dispatch', function() use ($di, $app) {
    $params = $app->router()->getCurrentRoute()->getParams();
    if (true === array_key_exists('site', $params)) {
        $di['sites']->setCurrent($params['site']);
    }
});

require XHGUI_ROOT_DIR . '/src/routes.php';

$app->run();
