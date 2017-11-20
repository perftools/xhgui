<?php
/**
 * Boostrapping and common utility definition.
 */
define('XHGUI_ROOT_DIR', dirname(__DIR__));

if (file_exists(XHGUI_ROOT_DIR . '/vendor/autoload.php')) {
    require XHGUI_ROOT_DIR . '/vendor/autoload.php';
} elseif (file_exists(XHGUI_ROOT_DIR . '/../../autoload.php')) {
    require XHGUI_ROOT_DIR . '/../../autoload.php';
}

$dir = dirname(__DIR__);
require_once $dir . '/src/Xhgui/Config.php';
$configDir = defined('XHGUI_CONFIG_DIR') ? XHGUI_CONFIG_DIR : $dir . '/config/';
if (file_exists($configDir . 'config.php')) {
    Xhgui_Config::load($configDir . 'config.php');
} else {
    Xhgui_Config::load($configDir . 'config.default.php');
}
unset($dir, $configDir);