<?php
/**
 * Boostrapping and common utility definition.
 */
define('XHGUI_ROOT_DIR', dirname(__FILE__));

require XHGUI_ROOT_DIR . '/Xhgui/Autoload.php';
Xhgui_Autoload::register();

Xhgui_Config::load(XHGUI_ROOT_DIR . '/config/config.php');
