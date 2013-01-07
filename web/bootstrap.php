<?php
/**
 * Boostrapping and common utility definition.
 */
define('ROOT_DIR', dirname(__FILE__));

require ROOT_DIR . '/Xhgui/Autoload.php';
require ROOT_DIR . '/vendor/Twig/Autoloader.php';

Xhgui_Autoload::register();
Twig_Autoloader::register();

Xhgui_Config::load(ROOT_DIR . '/config/config.php');
require ROOT_DIR . '/Xhgui/utility.php';
