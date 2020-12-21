<?php

namespace XHGui\ServiceProvider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Slim\App;
use Slim\Http\Environment;
use Slim\Http\Uri;
use Slim\Views\Twig;
use XHGui\Twig\TwigExtension;

class SlimProvider implements ServiceProviderInterface
{
    /**
     * Create the Slim app
     */
    public function register(Container $c): void
    {
        $c['view'] = static function ($app) {
            $view = new Twig($app['app.template_dir'], [
                'cache' => $app['app.cache_dir'],
            ]);

            // Instantiate and add Slim specific extension
            $router = $app->get('router');
            $uri = Uri::createFromEnvironment(new Environment($_SERVER));
            $view->addExtension(new \Slim\Views\TwigExtension($router, $uri));

            // set global variables to templates
            $view['date_format'] = $app['config']['date.format'];

            return $view;
        };

        $c['app'] = static function ($c) {
            if ($c['config']['timezone']) {
                date_default_timezone_set($c['config']['timezone']);
            }

            $app = new App($c['config']);

            $view = $c['view'];
            $view->parserExtensions = [
                new TwigExtension($app),
            ];
            $app->view($view);

            return $app;
        };
    }
}
