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
     * @staticVar array $loaded_files Files which have already been loaded
     *
     * @param string $file
     * @return void
     */
    public static function load($file)
    {
        static $loaded_files = array();

        if (in_array($file, $loaded_files)) {
            return;
        }

        $config = include($file);
        self::$_config = array_merge(self::$_config, $config);

        $loaded_files []= $file;
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

    /**
     * Called during profiler initialization
     *
     * Allows arbitrary conditions to be added configuring how
     * Xhgui profiles runs.
     *
     * @return boolean
     */
    public static function shouldRun()
    {
        $callback = self::read('profiler.enable');
        if (!is_callable($callback)) {
            return false;
        }
        return (bool)$callback();
    }

}
