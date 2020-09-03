<?php

use MongoDB\Driver\Manager;

/**
 * A small factory to handle creation of the profile saver instance.
 *
 * This class only exists to handle cases where an incompatible version of pimple
 * exists in the host application.
 */
class Xhgui_Saver
{
    /**
     * Get a saver instance based on configuration data.
     *
     * @param array $config The configuration data.
     * @return Xhgui_Saver_Interface
     */
    public static function factory($config)
    {
        switch ($config['save.handler']) {
            case 'pdo':
                if (!class_exists(PDO::class)) {
                    throw new RuntimeException("Required extension ext-pdo missing");
                }
                return new Xhgui_Saver_Pdo(
                    new PDO(
                        $config['pdo']['dsn'],
                        $config['pdo']['user'],
                        $config['pdo']['pass']
                    ),
                    $config['pdo']['table']
                );

            case 'mongodb':
                if (!class_exists(Manager::class)) {
                    throw new RuntimeException("Required extension ext-mongodb missing");
                }
                $mongo = new MongoClient($config['db.host'], $config['db.options'], $config['db.driverOptions']);
                $collection = $mongo->{$config['db.db']}->results;
                $collection->findOne();
                return new Xhgui_Saver_Mongo($collection);

            default:
                throw new RuntimeException("Unsupported save handler: {$config['save.handler']}");
        }
    }
}
