<?php
/**
 * Boostrapping and common utility definition.
 */
define('XHGUI_ROOT_DIR', dirname(__DIR__));
require XHGUI_ROOT_DIR . '/vendor/autoload.php';

Xhgui_Autoload::register();

Xhgui_Config::load(XHGUI_ROOT_DIR . '/config/config.php');
