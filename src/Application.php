<?php

namespace XHGui;

use Pimple\Container;
use Slim\App;
use XHGui\Saver\SaverInterface;

class Application extends Container
{
    /** @var bool */
    private $booted = false;

    public function __construct()
    {
        parent::__construct();
        $this->register(new ServiceProvider\ServiceProvider());
        $this->register(new ServiceProvider\ConfigProvider());
        $this->register(new ServiceProvider\PdoStorageProvider());
        $this->register(new ServiceProvider\MongoStorageProvider());
        $this->register(new ServiceProvider\SlimProvider());
    }

    public function run(): void
    {
        $this->boot()->getSlim()->run();
    }

    public function boot(): self
    {
        if (!$this->booted) {
            $this->register(new ServiceProvider\RouteProvider());
            $this->booted = true;
        }

        return $this;
    }

    public function getSlim(): App
    {
        return $this['app'];
    }

    public function getSaver(): SaverInterface
    {
        return $this['saver'];
    }
}
