<?php

namespace XHGui\ServiceProvider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use XHGui\Config;

class ConfigProvider implements ServiceProviderInterface
{
    public function register(Container $app): void
    {
        $app['config'] = static function ($app) {
            // @deprecated
            // define XHGUI_ROOT_DIR constant, config files may use it
            if (!defined('XHGUI_ROOT_DIR')) {
                define('XHGUI_ROOT_DIR', $app['app.dir']);
            }

            Config::load($app['app.config_dir'] . '/config.default.php');

            if (file_exists($app['app.config_dir'] . '/config.php')) {
                Config::load($app['app.config_dir'] . '/config.php');
            }

            $config = Config::all();
            $config += [
                'template_dir' => $app['app.dir'] . '/templates',
                'cache_dir' => $app['app.dir'] . '/cache',
            ];

            return $config;
        };
    }
}
