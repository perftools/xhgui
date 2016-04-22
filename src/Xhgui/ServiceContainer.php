<?php
use Slim\Slim;
use Slim\Views\Twig;
use Slim\Middleware\SessionCookie;

class Xhgui_ServiceContainer extends Pimple
{
    protected static $_instance;

    public static function instance()
    {
        if (empty(static::$_instance)) {
            static::$_instance = new self();
        }
        return static::$_instance;
    }

    public function __construct()
    {
        $this['config'] = Xhgui_Config::all();
        $this->_slimApp();
        $this->_services();
        $this->_controllers();
    }

    // Create the Slim app.
    protected function _slimApp()
    {
        $this['view'] = function ($c) {
            $cacheDir = isset($c['config']['cache']) ? $c['config']['cache'] : XHGUI_ROOT_DIR . '/cache';

            // Configure Twig view for slim
            $view = new Twig();

            $view->parserOptions = array(
                'charset' => 'utf-8',
                'cache' => $cacheDir,
                'auto_reload' => true,
                'strict_variables' => false,
                'autoescape' => true
            );

            return $view;
        };

        $this['app'] = $this->share(function ($c) {
            $app = new Slim($c['config']);

            // Enable cookie based sessions
            $app->add(new SessionCookie(array(
                'httponly' => true,
            )));

            // Add renderer.
            $app->add(new Xhgui_Middleware_Render());

            $view = $c['view'];
            $view->parserExtensions = array(
                new Xhgui_Twig_Extension($app)
            );
            $app->view($view);

            return $app;
        });
    }

    /**
     * Add common service objects to the container.
     */
    protected function _services()
    {
        $this['db'] = $this->share(function ($c) {
            $config = $c['config'];
            if (empty($config['db.options'])) {
                $config['db.options'] = array();
            }
            $mongo = new MongoClient($config['db.host'], $config['db.options']);
            $mongo->{$config['db.db']}->results->findOne();

            return $mongo->{$config['db.db']};
        });

        $this['watchFunctions'] = function ($c) {
            return new Xhgui_WatchFunctions($c['db']);
        };

        $this['profiles'] = function ($c) {
            return new Xhgui_Profiles($c['db']);
        };

        $this['saver'] = function($c) {
            $config = $c['config'];
            switch ($config['save.handler']) {
                case 'file':
                    return new Xhgui_Saver_File($config['save.handler.filename']);
                case 'mongodb':
                default:
                    return $c['saverMongo'];
            }
        };

        $this['saverMongo'] = function($c) {
            return new Xhgui_Saver_Mongo($c['profiles']);
        };
    }

    /**
     * Add controllers to the DI container.
     */
    protected function _controllers()
    {
        $this['watchController'] = function ($c) {
            return new Xhgui_Controller_Watch($c['app'], $c['watchFunctions']);
        };

        $this['runController'] = function ($c) {
            return new Xhgui_Controller_Run($c['app'], $c['profiles'], $c['watchFunctions']);
        };

        $this['customController'] = function ($c) {
            return new Xhgui_Controller_Custom($c['app'], $c['profiles']);
        };

        $this['waterfallController'] = function ($c) {
            return new Xhgui_Controller_Waterfall($c['app'], $c['profiles']);
        };
    }

}
