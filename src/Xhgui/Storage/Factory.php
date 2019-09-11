<?php

/**
 * Factory for storage classes
 */
class Xhgui_Storage_Factory
{
    /**
     * @param $config
     * @return Xhgui_Storage_File|Xhgui_Storage_Mongo|Xhgui_Storage_Pdo
     * @throws MongoConnectionException
     * @throws MongoException
     */
    public static function factory($config)
    {
        switch ($config['save.handler']) {
            case 'pdo':
                return new \Xhgui_Storage_Pdo($config);

            case 'mongodb':
                return new \Xhgui_Storage_Mongo($config);

            default:
            case 'file':
                return new \Xhgui_Storage_File($config);
        }
    }

    /**
     * For usage with factory instance - for example for easier testing
     *
     * @param $config
     * @return Xhgui_Storage_File|Xhgui_Storage_Mongo|Xhgui_Storage_Pdo
     * @throws MongoConnectionException
     * @throws MongoException
     */
    public function create($config)
    {
        return self::factory($config);
    }
}
