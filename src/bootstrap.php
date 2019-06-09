<?php
/**
 * Boostrapping and common utility definition.
 */
define('XHGUI_ROOT_DIR', dirname(__DIR__));

if (file_exists(XHGUI_ROOT_DIR . '/vendor/autoload.php')) {
    print "autoload 1\n";
    require XHGUI_ROOT_DIR . '/vendor/autoload.php';
} elseif (file_exists(XHGUI_ROOT_DIR . '/../../autoload.php')) {
    print "autoload 2\n";
    require XHGUI_ROOT_DIR . '/../../autoload.php';
}

Xhgui_Config::load(XHGUI_ROOT_DIR . '/config/config.default.php');
if (file_exists(XHGUI_ROOT_DIR . '/config/config.php')) {
    print "config loaded\n"
    Xhgui_Config::load(XHGUI_ROOT_DIR . '/config/config.php');
}
