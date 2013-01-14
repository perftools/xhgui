<?php
/**
 * Loads and reads config file.
 */
class Xhgui_Config
{
    static $_config = array();

    /**
     * Load a config file, it will replace
     * all the currently loaded configuration.
     *
     * @param string $file
     * @return void
     */
    public static function load($file)
    {
        $config = include($file);
        self::$_config = $config;
    }

    /**
     * Read a config value.
     *
     * @param string $name The name of the config variable
     * @return The value or null.
     */
    public static function read($name)
    {
        if (isset(self::$_config[$name])) {
            return self::$_config[$name];
        }
        return null;
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
        self::$_config[$name] = $value;
    }

    public static function clear()
    {
        self::$_config = array();
    }

}
