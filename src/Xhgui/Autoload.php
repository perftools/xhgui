<?php
class Xhgui_Autoload
{

    public static function register()
    {
        spl_autoload_register(array(new self, 'autoload'));
    }

    public function autoload($class)
    {
        if (strpos($class, 'Xhgui_') !== 0) {
            return;
        }
        $file = dirname(dirname(__FILE__)) . '/' . str_replace('_', '/', $class) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }

    public static function autoloadTwig()
    {
        static $complete;
        if (empty($complete)) {
            require XHGUI_ROOT_DIR . '/vendor/Twig/Autoloader.php';
            Twig_Autoloader::register();
            $complete = true;
        }
    }
}
