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
     * @return Xhgui_Saver_File|Xhgui_Saver_Mongo
     */
    public static function factory($config)
    {
        switch ($config['save.handler']) {
            case 'file':
                return new Xhgui_Saver_File($config['save.handler.filename']);
            case 'mongodb':
            default:
                $mongo = new MongoClient($config['db.host'], $config['db.options']);
                $mongo->{$config['db.db']}->results->findOne();
                $profiles = new Xhgui_Profiles($mongo->{$config['db.db']});
                return new Xhgui_Saver_Mongo($profiles);
        }
    }
}
