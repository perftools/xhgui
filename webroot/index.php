<?php
require dirname(__DIR__) . '/src/bootstrap.php';

use Slim\Slim;
use Slim\Views\Twig;
use Slim\Middleware\SessionCookie;

$config = include XHGUI_ROOT_DIR . '/config/config.php';
$app = new Slim($config);

// Configure Twig view for slim
$view = new Twig();
$view->parserOptions = array(
    'charset' => 'utf-8',
    'cache' => XHGUI_ROOT_DIR . '/cache',
    'auto_reload' => true,
    'strict_variables' => false,
    'autoescape' => true
);
$view->parserExtensions = array(
    new Xhgui_Twig_Extension($app)
);
$app->view($view);

// Enable cookie based sessions
$app->add(new SessionCookie(array(
    'httponly' => true,
)));

require XHGUI_ROOT_DIR . '/src/app/hooks.php';
require XHGUI_ROOT_DIR . '/src/app/controllers/runs.php';
require XHGUI_ROOT_DIR . '/src/app/controllers/custom.php';
require XHGUI_ROOT_DIR . '/src/app/controllers/watch.php';
require XHGUI_ROOT_DIR . '/src/app/controllers/error.php';

$app->run();
