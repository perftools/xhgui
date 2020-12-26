<?php

namespace XHGui\ServiceProvider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Psr\Container\ContainerInterface;
use Slim\App;
use Slim\Container as SlimContainer;
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
        $c['app'] = function ($c) {
            if ($c['config']['timezone']) {
                date_default_timezone_set($c['config']['timezone']);
            }

            $app = new App($c['config']);
            $this->registerView($app->getContainer());

            /*
            $view = $c['view'];
            */

            return $app;
        };
    }

    private function registerView(ContainerInterface $c): void
    {
        $c['view'] = static function (SlimContainer $app) {
            $view = new Twig($app['template_dir'], [
                'cache' => $app['cache_dir'],
            ]);

            // Instantiate and add Slim specific extension
            $router = $app->get('router');
            $uri = Uri::createFromEnvironment(new Environment($_SERVER));
            $view->addExtension(new \Slim\Views\TwigExtension($router, $uri));
            $view->addExtension(new TwigExtension($router, $uri));

            // set global variables to templates
            $view['date_format'] = $app['date.format'];

            return $view;
        };
    }
}
