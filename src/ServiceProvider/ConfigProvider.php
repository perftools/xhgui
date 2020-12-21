<?php

namespace XHGui\ServiceProvider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use XHGui\Config;

class ConfigProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['config'] = static function () {
            return Config::all();
        };
    }
}
