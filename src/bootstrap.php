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
