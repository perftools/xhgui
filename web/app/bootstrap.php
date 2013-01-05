<?php
/**
 * Boostrapping and common utility definition.
 */
define('ROOT_DIR', dirname(__DIR__));
define('DATE_FORMAT', 'M jS H:i:s');
define('PIE_COUNT', 5);
define('DETAIL_COUNT', 6);
define('DISPLAY_LIMIT', 25);

require ROOT_DIR . '/vendor/Twig/Autoloader.php';
Twig_Autoloader::register();

require ROOT_DIR . '/app/utility.php';
require ROOT_DIR . '/app/Twig/XhguiExtension.php';

function load_template($name) {
    static $environment;
    if (empty($environment)) {
        $loader = new Twig_Loader_Filesystem(ROOT_DIR . '/templates/');
        $environment = new Twig_Environment($loader, array(
            'cache' => ROOT_DIR . '/cache',
            'debug' => true,
        ));
        $environment->addExtension(new Twig_XhguiExtension());
    }
    return $environment->loadTemplate($name);
}
