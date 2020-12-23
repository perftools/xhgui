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
     */
    public static function load($file): void
    {
        $config = include $file;
        self::$config = array_merge(self::$config, $config);
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
}
