<?php

/**
 * Factory for storage classes
 */
class Xhgui_Storage_Factory
{
    /**
     * @param $config
     * @return Xhgui_Storage_File|Xhgui_Storage_Mongo|Xhgui_Storage_PDO
     * @throws MongoConnectionException
     * @throws MongoException
     */
    public static function factory($config)
    {
        switch ($config['save.handler']) {
            case 'pdo':
                return new \Xhgui_Storage_PDO($config);

            case 'mongodb':
                return new \Xhgui_Storage_Mongo($config);

            default:
            case 'file':
                return new \Xhgui_Storage_File($config);
        }
    }
}
