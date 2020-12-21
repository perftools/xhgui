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
            Config::load(XHGUI_ROOT_DIR . '/config/config.default.php');

            if (file_exists(XHGUI_ROOT_DIR . '/config/config.php')) {
                Config::load(XHGUI_ROOT_DIR . '/config/config.php');
            }

            return Config::all();
        };
    }
}
