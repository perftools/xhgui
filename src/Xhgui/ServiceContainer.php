<?php

use MongoDB\Driver\Manager;
use Pimple\Container;
use Slim\Slim;
use Slim\Views\Twig;
use Slim\Middleware\SessionCookie;

class Xhgui_ServiceContainer extends Container
{
    /** @var self */
    protected static $_instance;

    /**
     * @return self
     */
    public static function instance()
    {
        if (empty(static::$_instance)) {
            static::$_instance = new self();
        }
        return static::$_instance;
    }

    public function __construct()
    {
        parent::__construct();
        $this->_slimApp();
        $this->_services();
        $this->_controllers();
    }

    // Create the Slim app.
    protected function _slimApp()
    {
        $this['view'] = static function ($c) {
            $cacheDir = $c['config']['cache'] ?? XHGUI_ROOT_DIR . '/cache';

            // Configure Twig view for slim
            $view = new Twig();

            $view->twigTemplateDirs = [dirname(__DIR__) . '/templates'];
            $view->parserOptions = [
                'charset' => 'utf-8',
                'cache' => $cacheDir,
                'auto_reload' => true,
                'strict_variables' => false,
                'autoescape' => true,
            ];

            return $view;
        };

        $this['app'] = static function ($c) {
            if ($c['config']['timezone']) {
                date_default_timezone_set($c['config']['timezone']);
            }

            $app = new Slim($c['config']);

            // Enable cookie based sessions
            $app->add(new SessionCookie([
                'httponly' => true,
            ]));

            // Add renderer.
            $app->add(new Xhgui_Middleware_Render());

            $view = $c['view'];
            $view->parserExtensions = [
                new Xhgui_Twig_Extension($app),
            ];
            $app->view($view);

            return $app;
        };
    }

    /**
     * Add common service objects to the container.
     */
    protected function _services()
    {
        $this['config'] = Xhgui_Config::all();

        $this['db'] = static function ($c) {
            $config = $c['config'];
            if (empty($config['db.options'])) {
                $config['db.options'] = [];
            }
            if (empty($config['db.driverOptions'])) {
                $config['db.driverOptions'] = [];
            }
            $mongo = new MongoClient($config['db.host'], $config['db.options'], $config['db.driverOptions']);
            $mongo->{$config['db.db']}->results->findOne();

            return $mongo->{$config['db.db']};
        };

        $this['pdo'] = static function ($c) {
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ];
            return new PDO(
                $c['config']['pdo']['dsn'],
                $c['config']['pdo']['user'],
                $c['config']['pdo']['pass'],
                $options
            );
        };

        $this['searcher.mongodb'] = static function ($c) {
            return new Xhgui_Searcher_Mongo($c['db']);
        };

        $this['searcher.pdo'] = static function ($c) {
            return new Xhgui_Searcher_Pdo($c['pdo'], $c['config']['pdo']['table']);
        };

        $this['searcher'] = static function ($c) {
            $saver = $c['config']['save.handler'];

            return $c["searcher.$saver"];
        };

        $this['saver.mongodb'] = static function ($c) {
            $config = $c['config'];

            if (!class_exists(Manager::class)) {
                throw new RuntimeException("Required extension ext-mongodb missing");
            }
            $mongo = new MongoClient($config['db.host'], $config['db.options'], $config['db.driverOptions']);
            $collection = $mongo->{$config['db.db']}->results;
            $collection->findOne();

            return new Xhgui_Saver_Mongo($collection);
        };

        $this['saver.pdo'] = static function ($c) {
            $config = $c['config'];

            if (!class_exists(PDO::class)) {
                throw new RuntimeException("Required extension ext-pdo is missing");
            }

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ];

            return new Xhgui_Saver_Pdo(
                new PDO(
                    $config['pdo']['dsn'],
                    $config['pdo']['user'],
                    $config['pdo']['pass'],
                    $options
                ),
                $config['pdo']['table']
            );
        };

        $this['saver'] = static function ($c) {
            $saver = $c['config']['save.handler'];

            return $c["saver.$saver"];
        };
    }

    /**
     * Add controllers to the DI container.
     */
    protected function _controllers()
    {
        $this['watchController'] = $this->factory(static function ($c) {
            return new Xhgui_Controller_Watch($c['app'], $c['searcher']);
        });

        $this['runController'] = $this->factory(static function ($c) {
            return new Xhgui_Controller_Run($c['app'], $c['searcher']);
        });

        $this['customController'] = $this->factory(static function ($c) {
            return new Xhgui_Controller_Custom($c['app'], $c['searcher']);
        });

        $this['waterfallController'] = $this->factory(static function ($c) {
            return new Xhgui_Controller_Waterfall($c['app'], $c['searcher']);
        });

        $this['importController'] = $this->factory(static function ($c) {
            return new Xhgui_Controller_Import($c['app'], $c['saver'], $c['config']['upload.token']);
        });

        $this['metricsController'] = $this->factory(static function ($c) {
            return new Xhgui_Controller_Metrics($c['app'], $c['searcher']);
        });
    }
}
