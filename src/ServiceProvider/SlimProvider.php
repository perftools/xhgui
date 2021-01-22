<?php

namespace XHGui\ServiceProvider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Slim\Middleware\SessionCookie;
use Slim\Slim as App;
use Slim\Views\Twig;
use XHGui\Twig\TwigExtension;

class SlimProvider implements ServiceProviderInterface
{
    /**
     * Create the Slim app
     */
    public function register(Container $c): void
    {
        $c['view'] = static function ($c) {
            // Configure Twig view for slim
            $view = new Twig();

            $view->twigTemplateDirs = [
                $c['app.template_dir'],
            ];
            $view->parserOptions = [
                'charset' => 'utf-8',
                'cache' => $c['app.cache_dir'],
                'auto_reload' => true,
                'strict_variables' => false,
                'autoescape' => 'html',
            ];

            // set global variables to templates
            $view->appendData([
                'date_format' => $c['config']['date.format'],
            ]);

            return $view;
        };

        $c['app'] = static function ($c) {
            if ($c['config']['timezone']) {
                date_default_timezone_set($c['config']['timezone']);
            }

            $app = new App($c['config']);

            // Enable cookie based sessions
            $app->add(new SessionCookie([
                'httponly' => true,
            ]));

            $view = $c['view'];
            $view->parserExtensions = [
                new TwigExtension($app),
            ];
            $app->view($view);

            return $app;
        };
    }
}
