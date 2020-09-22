<?php
/**
 * Boostrapping and common utility definition.
 */

use XHGui\Config;

define('XHGUI_ROOT_DIR', dirname(__DIR__));

if (file_exists(XHGUI_ROOT_DIR . '/vendor/autoload.php')) {
    require XHGUI_ROOT_DIR . '/vendor/autoload.php';
} elseif (file_exists(XHGUI_ROOT_DIR . '/../../autoload.php')) {
    require XHGUI_ROOT_DIR . '/../../autoload.php';
}

Config::load(XHGUI_ROOT_DIR . '/config/config.default.php');
if (file_exists(XHGUI_ROOT_DIR . '/config/config.php')) {
    Config::load(XHGUI_ROOT_DIR . '/config/config.php');
}
