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
        try {
            self::$_mongo = new MongoClient($host);
            self::$_db = self::$_mongo->{$db};
            return self::$_db;
        } catch (Exception $e) {
            echo "Unable to connect to Mongo<br>\n";
            echo "Exception: " . $e->getMessage() ."<br>\n";
            echo "You may want to ensure that Mongo has been started, and that the config file has the right connection information";
            exit;
        }
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
