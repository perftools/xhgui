<?php

namespace XHGui\ServiceProvider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Psr\Container\ContainerInterface;
use Slim\App;
use Slim\Container as SlimContainer;
use Slim\Flash;
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

            return $app;
        };
    }

    private function registerSlimContainer(ContainerInterface $container): void
    {
        $container['view'] = static function (SlimContainer $container) {
            $view = new Twig($container['template_dir'], [
                'cache' => $container['cache_dir'],
            ]);

            $view->addExtension($container[TwigExtension::class]);

            // set global variables to templates
            $view['date_format'] = $container['date.format'];

            return $view;
        };

        $container['flash'] = static function () {
            return new Flash\Messages();
        };

        $container[TwigExtension::class] = static function (SlimContainer $container) {
            $router = $container->get('router');
            $request = $container->get('request');
            $pathPrefix = $container->get('path.prefix');

            return new TwigExtension($router, $request, $pathPrefix);
        };

        $container[Uri::class] = static function (SlimContainer $container) {
            $env = $container->get('environment');

            return Uri::createFromEnvironment($env);
        };

        $container['request.proxy'] = static function (SlimContainer $container) {
            return new RequestProxy($container['request']);
        };
    }
}
