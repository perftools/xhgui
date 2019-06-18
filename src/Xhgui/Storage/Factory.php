<?php


class Xhgui_Storage_Factory
{
    public static function factory($config) {
        switch($config['save.handler']) {
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
