<?php

function splitName($name)
{
    //we have a==>b or just a
    $a = explode("==>", $name);
    if (isset($a[1]))
    {
        return $a;
    }

    return array(null, $a[0]);
}



/**
 * Creates a simplified URL given a standard URL.
 * Does the following transformations:
 *
 * - Remove numeric values after =.
 *
 * @param string $url
 * @return string
 */
function simpleUrl($url)
{
    $url = preg_replace('/\=\d+/', '', $url);
    // TODO Add hooks for customizing this.
    return $url;
}

/**
 * Load twig templates using the shared environment object.
 *
 * @param string $name The name of the template.
 * @return Twig_Template
 */
function load_template($name) {
    static $environment;
    if (empty($environment)) {
        autoloadTwig();

        $loader = new Twig_Loader_Filesystem(XHGUI_ROOT_DIR . '/templates/');
        $environment = new Twig_Environment($loader, array(
            'cache' => XHGUI_ROOT_DIR . '/cache',
            'debug' => true,
        ));
        $environment->addExtension(new Xhgui_Twig_Extension());
    }
    return $environment->loadTemplate($name);
}

function autoloadTwig()
{
    static $complete;
    if (empty($complete)) {
        require XHGUI_ROOT_DIR . '/vendor/Twig/Autoloader.php';
        Twig_Autoloader::register();
        $complete = true;
    }
}
