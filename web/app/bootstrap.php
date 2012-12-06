<?php
/**
 * Boostrapping and common utility definition.
 */
define('ROOT_DIR', dirname(__DIR__));

require ROOT_DIR . '/vendor/Twig/Autoloader.php';
Twig_Autoloader::register();


function load_template($name) {
    static $environment;
    if (empty($environment)) {
        $loader = new Twig_Loader_Filesystem(ROOT_DIR . '/templates/');
        $environment = new Twig_Environment($loader);
    }
    return $environment->loadTemplate($name);
}
