<?php

namespace XHGui;

/**
 * Loads and reads config file.
 */
class Config
{
    private static $config = [];

    /**
     * Load a config file, it will replace
     * all the currently loaded configuration.
     *
     * @return void
     */
    public static function load($file)
    {
        $config = include $file;
        self::$config = array_merge(self::$config, $config);
    }

    /**
     * Read a config value.
     *
     * @param string $name The name of the config variable
     * @return mixed The value or null.
     */
    public static function read($name)
    {
        if (isset(self::$config[$name])) {
            return self::$config[$name];
        }

        return null;
    }

    /**
     * Get all the configuration options.
     *
     * @return array
     */
    public static function all()
    {
        return self::$config;
    }

    /**
     * Write a config value.
     *
     * @param string $name The name of the config variable
     * @param mixed $value The value of the config variable
     * @return void
     */
    public static function write($name, $value)
    {
        self::$config[$name] = $value;
    }

    /**
     * Clear out the data stored in the config class.
     *
     * @return void
     */
    public static function clear()
    {
        self::$config = [];
    }
}
