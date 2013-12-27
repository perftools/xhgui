<?php
/**
 * Bootstrapping and common utility definition.
 */
define('XHGUI_ROOT_DIR', dirname(__DIR__));

if (file_exists(XHGUI_ROOT_DIR . '/../autoload.php')) {
	require XHGUI_ROOT_DIR . '/../autoload.php';
} else {
	require XHGUI_ROOT_DIR . '/vendor/autoload.php';
}

Xhgui_Config::load(XHGUI_ROOT_DIR . '/config/config.default.php');
Xhgui_Config::load(XHGUI_ROOT_DIR . '/config/config.php');
