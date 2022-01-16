<?php

namespace XHGui\ServiceProvider;

use PDO;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RuntimeException;
use XHGui\Db\PdoRepository;
use XHGui\Saver\PdoSaver;
use XHGui\Searcher\PdoSearcher;

class PdoStorageProvider implements ServiceProviderInterface
{
    public function register(Container $app): void
    {
        $app['pdo.driver'] = static function ($app) {
            return explode(':', $app['config']['pdo']['dsn'], 2)[0];
        };

        $app['pdo'] = static function ($app) {
            if (!class_exists(PDO::class)) {
                throw new RuntimeException('Required extension ext-pdo is missing');
            }

            $driver = $app['pdo.driver'];

            // check the PDO driver is available
            if (!in_array($driver, PDO::getAvailableDrivers(), true)) {
                $drivers = implode(',', PDO::getAvailableDrivers()) ?: '(none)';
                throw new RuntimeException("Required PDO driver $driver is missing, Available drivers: $drivers");
            }

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ];

            if ($driver === 'mysql') {
                $options[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET SQL_MODE=ANSI_QUOTES;';
            }

            return new PDO(
                $app['config']['pdo']['dsn'],
                $app['config']['pdo']['user'],
                $app['config']['pdo']['pass'],
                $options
            );
        };

        $app[PdoRepository::class] = static function ($app) {
            return new PdoRepository(
                $app['pdo'],
                $app['pdo.driver'],
                $app['config']['pdo']['table'],
                $app['config']['pdo']['tableWatch']
            );
        };

        $app['searcher.pdo'] = static function ($app) {
            return new PdoSearcher($app[PdoRepository::class]);
        };

        $app['saver.pdo'] = static function ($app) {
            return new PdoSaver($app[PdoRepository::class]);
        };
    }
}
