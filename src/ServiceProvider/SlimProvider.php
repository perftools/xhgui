<?php

namespace XHGui\ServiceProvider;

use Pimple\Container as PimpleContainer;
use Pimple\Psr11\Container as PsrContainer;
use Pimple\ServiceProviderInterface;
use Psr\Container\ContainerInterface;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Flash;
//use Slim\Http\Uri;
use Slim\Views\Twig;
use XHGui\AppContainer;
use XHGui\RequestProxy;
use XHGui\ResponseProxy;
use XHGui\Twig\TwigExtension;

class SlimProvider implements ServiceProviderInterface
{
    /**
     * Create the Slim app
     */
    public function register(PimpleContainer $pimple): void
    {
        $pimple['app'] = function ($c) use ($pimple) {
            if ($c['config']['timezone']) {
                date_default_timezone_set($c['config']['timezone']);
            }

            $app = AppFactory::create(
                container: $pimple,
            );
            $app->addRoutingMiddleware();
            $this->registerSlimContainer($pimple);

            return $app;
        };
    }

    private function registerSlimContainer(PimpleContainer $container): void
    {
        $container['view.class'] = Twig::class;
        $container['view'] = static function (PimpleContainer $container) {
            $view = new $container['view.class']($container['template_dir'], [
                'cache' => $container['cache_dir'],
            ]);

            $view->addExtension($container[TwigExtension::class]);

            // set global variables to templates
            $view['date_format'] = $container['date.format'];

            return $view;
        };

        // Having "null" here will make use of $_SESSION
        $container['flash.storage'] = null;
        $container['flash'] = static function ($container) {
            $storage = $container['flash.storage'];

            return new Flash\Messages($storage);
        };

        $container[TwigExtension::class] = static function (AppContainer $container) {
            $router = $container->get('router');
            $request = $container->get('request');
            $pathPrefix = $container->get('path.prefix');

            return new TwigExtension($router, $request, $pathPrefix);
        };

        $container[Uri::class] = static function (AppContainer $container) {
            $env = $container->get('environment');

            return Uri::createFromEnvironment($env);
        };

        $container['request.proxy'] = static fn(AppContainer $container) => new RequestProxy($container['request']);
        $container['response.proxy'] = static fn(AppContainer $container) => new ResponseProxy($container['response']);

        $container['response.final'] = static function (AppContainer $container) {
            /** @var ResponseProxy $response */
            $response = $container['response.proxy'];

            return $response->getResponse();
        };
    }
}
