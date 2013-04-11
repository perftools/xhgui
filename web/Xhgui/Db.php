<?php

class Xhgui_Db
{
    protected static $_db;
    protected static $_mongo;

    /**
     * Connect to mongo, and get the configured database
     *
     * Will return the active database if called multiple times.
     *
     * @return MongoDb
     */
    public static function connect($host = null, $db = null)
    {
        if (!empty(self::$_db)) {
            return self::$_db;
        }
        if (empty($host)) {
            $host = Xhgui_Config::read('db.host');
        }
        if (empty($db)) {
            $db = Xhgui_Config::read('db.db');
        }
        self::$_mongo = new MongoClient($host);
        self::$_db = self::$_mongo->{$db};
        return self::$_db;
    }

    /**
     * Get the connection/MongoClient object.
     *
     * @return MongoClient
     */
    public static function getConnection()
    {
        return self::$_mongo;
    }

}
