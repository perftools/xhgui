<?php

namespace XHGui;

use Pimple\Container;
use XHGui\Saver\NormalizingSaver;

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
        $this->register(new ServiceProvider\SlimProvider());
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
