<?php

namespace XHGui\ServiceProvider;

use MongoClient;
use MongoCollection;
use MongoDB;
use MongoDB\Driver\Manager;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RuntimeException;
use XHGui\Saver\MongoSaver;
use XHGui\Searcher\MongoSearcher;

class MongoStorageProvider implements ServiceProviderInterface
{
    public function register(Container $app): void
    {
        // NOTE: db.host, db.options, db.driverOptions, db.db are @deprecated and will be removed in the future
        $app['mongodb.database'] = static function ($app) {
            $config = $app['config'];
            $mongodb = $config['mongodb'] ?? [];

            return $config['db.db'] ?? $mongodb['database'] ?? 'xhgui';
        };

        $app[MongoDB::class] = static function ($app) {
            $database = $app['mongodb.database'];
            /** @var MongoClient $client */
            $client = $app[MongoClient::class];
            $mongoDB = $client->selectDb($database);
            $mongoDB->results->findOne();

            return $mongoDB;
        };

        $app[MongoClient::class] = static function ($app) {
            if (!class_exists(Manager::class)) {
                throw new RuntimeException('Required extension ext-mongodb missing');
            }

            $config = $app['config'];
            $mongodb = $config['mongodb'] ?? [];
            $options = $config['db.options'] ?? $mongodb['options'] ?? [];
            $driverOptions = $config['db.driverOptions'] ?? $mongodb['driverOptions'] ?? [];
            $server = $config['db.host'] ?? sprintf('mongodb://%s:%s', $mongodb['hostname'], $mongodb['port']);

            return new MongoClient($server, $options, $driverOptions);
        };

        $app['searcher.mongodb'] = static function ($app) {
            return new MongoSearcher($app[MongoDB::class]);
        };

        $app['saver.mongodb'] = static function ($app) {
            /** @var MongoDB $mongoDB */
            $mongoDB = $app[MongoDB::class];
            /** @var MongoCollection $collection */
            $collection = $mongoDB->results;

            return new MongoSaver($collection);
        };
    }
}
