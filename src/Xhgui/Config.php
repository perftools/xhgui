<?php
/**
 * Loads and reads config file.
 */
class Xhgui_Config
{
    private static $_config = array();

    /**
     * Load a config file, it will replace
     * all the currently loaded configuration.
     *
     * @return void
     */
    public static function load($file)
    {
        $config = include $file;
        self::$_config = array_merge(self::$_config, $config);
    }

    /**
     * Read a config value.
     *
     * @param string $name The name of the config variable
     * @return mixed The value or null.
     */
    public static function read($name)
    {
        if (isset(self::$_config[$name])) {
            return self::$_config[$name];
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
        return self::$_config;
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

    /**
     * Clear out the data stored in the config class.
     *
     * @return void
     */
    public static function clear()
    {
        self::$_config = array();
    }
}
