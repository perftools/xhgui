<?php

namespace XHGui\ServiceProvider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use XHGui\Config;

class ConfigProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['config'] = static function ($app) {
            Config::load($app['app.config_dir'] . '/config.default.php');

            if (file_exists($app['app.config_dir'] . '/config.php')) {
                Config::load($app['app.config_dir'] . '/config.php');
            }

            return Config::all();
        };
    }
}
