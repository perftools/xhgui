<?php
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
            case 'file':
                return new Xhgui_Saver_File($config['save.handler.filename']);

            case 'upload':
                $timeout = 3;
                if (isset($config['save.handler.upload.timeout'])) {
                    $timeout = $config['save.handler.upload.timeout'];
                }
                return new Xhgui_Saver_Upload(
                    $config['save.handler.upload.uri'],
                    $timeout
                );

            case 'pdo':
                return new Xhgui_Saver_Pdo(
                    new PDO(
                        $config['save.handler.pdo']['dsn'],
                        $config['save.handler.pdo']['user'],
                        $config['save.handler.pdo']['pass']
                    ),
                    $config['save.handler.pdo']['table']
                );
            case 'mongodb':
            default:
                $mongo = new MongoClient($config['db.host'], $config['db.options']);
                $collection = $mongo->{$config['db.db']}->results;
                $collection->findOne();
                return new Xhgui_Saver_Mongo($collection);
        }
    }
}
