<?php
/**
 * Boostrapping and common utility definition.
 */
define('ROOT_DIR', dirname(__FILE__));
define('DATE_FORMAT', 'M jS H:i:s');
define('PIE_COUNT', 5);
define('DETAIL_COUNT', 6);
define('DISPLAY_LIMIT', 25);

require ROOT_DIR . '/Xhgui/Autoload.php';
Xhgui_Autoload::register();

require ROOT_DIR . '/Xhgui/utility.php';

require ROOT_DIR . '/vendor/Twig/Autoloader.php';
Twig_Autoloader::register();
