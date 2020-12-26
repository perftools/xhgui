<?php

namespace XHGui;

use Pimple\Container;

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
        $this->register(new ServiceProvider\ServiceProvider());
        $this->register(new ServiceProvider\ConfigProvider());
        $this->register(new ServiceProvider\PdoStorageProvider());
        $this->register(new ServiceProvider\MongoStorageProvider());
        $this->register(new ServiceProvider\SlimProvider());
    }

    public function boot(): void
    {
        $this->register(new ServiceProvider\RouteProvider());
    }
}
