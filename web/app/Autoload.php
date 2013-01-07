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
        $file = dirname(__FILE__) . str_replace('_', '/', $class) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
}
