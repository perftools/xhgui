<?php

namespace XHGui\ServiceProvider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use XHGui\Saver\NormalizingSaver;

class ServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app): void
    {
        $this->setupPaths($app);
        $this->setupServices($app);
    }

    private function setupPaths(Container $app): void
    {
        $app['app.dir'] = dirname(__DIR__, 2);
        $app['app.config_dir'] = $app['app.dir'] . '/config';
    }

    /**
     * Add common service objects to the container.
     */
    private function setupServices(Container $app): void
    {
        $app['searcher'] = static function ($app) {
            $saver = $app['config']['save.handler'];

            return $app["searcher.$saver"];
        };

        $app['saver'] = static function ($app) {
            $saver = $app['config']['save.handler'];

            return new NormalizingSaver($app["saver.$saver"]);
        };
    }
}
