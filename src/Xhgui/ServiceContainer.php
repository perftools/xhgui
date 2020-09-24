<?php

namespace XHGui;

use MongoClient;
use MongoDB\Driver\Manager;
use PDO;
use Pimple\Container;
use RuntimeException;
use Slim\Middleware\SessionCookie;
use Slim\Slim;
use Slim\Views\Twig;
use XHGui\Db\PdoRepository;
use XHGui\Middleware\RenderMiddleware;
use XHGui\Searcher\MongoSearcher;
use XHGui\Searcher\PdoSearcher;
use XHGui\Twig\TwigExtension;

class ServiceContainer extends Container
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
            $app->add(new RenderMiddleware());

            $view = $c['view'];
            $view->parserExtensions = [
                new TwigExtension($app),
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
        $this['config'] = Config::all();

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
            if (!class_exists(PDO::class)) {
                throw new RuntimeException("Required extension ext-pdo is missing");
            }

            $adapter = explode(':', $c['config']['pdo']['dsn'], 2)[0];
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ];

            if ($adapter === 'mysql') {
                $options[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET SQL_MODE=ANSI_QUOTES;';
            }

            return new PDO(
                $c['config']['pdo']['dsn'],
                $c['config']['pdo']['user'],
                $c['config']['pdo']['pass'],
                $options
            );
        };

        $this[PdoRepository::class] = static function ($c) {
            return new PdoRepository($c['pdo'], $c['config']['pdo']['table']);
        };

        $this['searcher.mongodb'] = static function ($c) {
            return new MongoSearcher($c['db']);
        };

        $this['searcher.pdo'] = static function ($c) {
            return new PdoSearcher($c[PdoRepository::class]);
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

            return new Saver\MongoSaver($collection);
        };

        $this['saver.pdo'] = static function ($c) {
            $config = $c['config'];

            return new Saver\PdoSaver(
                $c['pdo'],
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
            return new Controller\WatchController($c['app'], $c['searcher']);
        });

        $this['runController'] = $this->factory(static function ($c) {
            return new Controller\RunController($c['app'], $c['searcher']);
        });

        $this['customController'] = $this->factory(static function ($c) {
            return new Controller\CustomController($c['app'], $c['searcher']);
        });

        $this['waterfallController'] = $this->factory(static function ($c) {
            return new Controller\WaterfallController($c['app'], $c['searcher']);
        });

        $this['importController'] = $this->factory(static function ($c) {
            return new Controller\ImportController($c['app'], $c['saver'], $c['config']['upload.token']);
        });

        $this['metricsController'] = $this->factory(static function ($c) {
            return new Controller\MetricsController($c['app'], $c['searcher']);
        });
    }
}
