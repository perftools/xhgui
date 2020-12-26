<?php

namespace XHGui;

use Pimple\Container;
use Slim\Slim as App;
use Slim\Views\Twig;
use XHGui\Saver\NormalizingSaver;
use XHGui\Twig\TwigExtension;

class ServiceContainer extends Container
{
    /** @var self */
    protected static $_instance;

    /**
     * @return self
     */
    public static function instance()
    {
        if (empty(static::$_instance)) {
            static::$_instance = new self();
            static::$_instance->boot();
        }

        return static::$_instance;
    }

    public function __construct()
    {
        parent::__construct();
        $this->setupPaths($this);
        $this->register(new ServiceProvider\ConfigProvider());
        $this->register(new ServiceProvider\PdoStorageProvider());
        $this->register(new ServiceProvider\MongoStorageProvider());
        $this->slimApp();
        $this->services();
    }

    public function boot(): void
    {
        $this->register(new ServiceProvider\RouteProvider());
    }

    private function setupPaths(self $app): void
    {
        $app['app.dir'] = dirname(__DIR__);
        $app['app.template_dir'] = dirname(__DIR__) . '/templates';
        $app['app.config_dir'] = dirname(__DIR__) . '/config';
        $app['app.cache_dir'] = static function ($c) {
            return $c['config']['cache'] ?? dirname(__DIR__) . '/cache';
        };
    }

    // Create the Slim app.
    private function slimApp(): void
    {
        $this['view'] = static function ($c) {
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

        $this['app'] = static function ($c) {
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

    /**
     * Add common service objects to the container.
     */
    private function services(): void
    {
        $this['searcher'] = static function ($c) {
            $saver = $c['config']['save.handler'];

            return $c["searcher.$saver"];
        };

        $this['saver'] = static function ($c) {
            $saver = $c['config']['save.handler'];

            return new NormalizingSaver($c["saver.$saver"]);
        };
    }
}
