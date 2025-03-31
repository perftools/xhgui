<?php

namespace XHGui;

use Slim\App;
use XHGui\Saver\SaverInterface;

class Application
{
    private bool $booted = false;
    private AppContainer $container;

    public function __construct()
    {
        $this->container = $container = new AppContainer();
        $container->register(new ServiceProvider\ServiceProvider());
        $container->register(new ServiceProvider\ConfigProvider());
        $container->register(new ServiceProvider\PdoStorageProvider());
        $container->register(new ServiceProvider\MongoStorageProvider());
        $container->register(new ServiceProvider\SlimProvider());
    }

    public function run(): void
    {
        $this->boot()->getSlim()->run();
    }

    public function boot(): self
    {
        if (!$this->booted) {
            $this->container->register(new ServiceProvider\RouteProvider());
            $this->booted = true;
        }

        return $this;
    }

    public function getContainer(): AppContainer
    {
        return $this->container;
    }

    public function getSlim(): App
    {
        return $this->container['app'];
    }

    public function getSaver(): SaverInterface
    {
        return $this->container['saver'];
    }
}
