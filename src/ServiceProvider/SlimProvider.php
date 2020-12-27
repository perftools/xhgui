<?php

namespace XHGui\ServiceProvider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Psr\Container\ContainerInterface;
use Slim\App;
use Slim\Container as SlimContainer;
use Slim\Http\Uri;
use Slim\Views\Twig;
use XHGui\RequestProxy;
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
            $this->registerSlimContainer($app->getContainer());

            /*
            $view = $c['view'];
            */

            return $app;
        };
    }

    private function registerSlimContainer(ContainerInterface $c): void
    {
        $c['view.class'] = Twig::class;
        $c['view'] = static function (SlimContainer $app) {
            $view = new $app['view.class']($app['template_dir'], [
                'cache' => $app['cache_dir'],
            ]);

            // Instantiate and add Slim specific extension
            $router = $app->get('router');
            $env = $app->get('environment');
            $uri = Uri::createFromEnvironment($env);
            $view->addExtension(new \Slim\Views\TwigExtension($router, $uri));
            $view->addExtension(new TwigExtension($router, $uri));

            // set global variables to templates
            $view['date_format'] = $app['date.format'];

            return $view;
        };

        $c['request.proxy'] = static function (SlimContainer $app) {
            return new RequestProxy($app['request']);
        };
    }
}
