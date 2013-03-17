<?php

class Xhgui_Template
{
    protected static $_env;

    /**
     * Load twig templates using the shared environment object.
     *
     * @param string $name The name of the template.
     * @return Twig_Template
     */
    public static function load($name)
    {
        if (empty(self::$_env)) {
            Xhgui_Autoload::autoloadTwig();

            $loader = new Twig_Loader_Filesystem(XHGUI_ROOT_DIR . '/templates/');
            $env= new Twig_Environment($loader, array(
                'cache' => XHGUI_ROOT_DIR . '/cache',
                'debug' => true,
            ));
            $env->addExtension(new Xhgui_Twig_Extension());
            self::$_env = $env;
        }
        return self::$_env->loadTemplate($name);
    }

}
