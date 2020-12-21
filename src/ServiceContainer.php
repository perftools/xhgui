<?php

namespace XHGui;

use MongoClient;
use MongoCollection;
use MongoDB;
use MongoDB\Driver\Manager;
use PDO;
use Pimple\Container;
use RuntimeException;
use Slim\Middleware\SessionCookie;
use Slim\Slim as App;
use Slim\Views\Twig;
use XHGui\Db\PdoRepository;
use XHGui\Middleware\RenderMiddleware;
use XHGui\Saver\NormalizingSaver;
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
        $this->storageDriverPdo($this);
        $this->storageDriverMongoDb($this);
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

            $app = new App($c['config']);

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

        $this['searcher'] = static function ($c) {
            $saver = $c['config']['save.handler'];

            return $c["searcher.$saver"];
        };

        $this['saver'] = static function ($c) {
            $saver = $c['config']['save.handler'];

            return new NormalizingSaver($c["saver.$saver"]);
        };
    }

    private function storageDriverPdo(Container $app)
    {
        $app['pdo'] = static function ($app) {
            if (!class_exists(PDO::class)) {
                throw new RuntimeException('Required extension ext-pdo is missing');
            }

            $driver = explode(':', $app['config']['pdo']['dsn'], 2)[0];

            // check the PDO driver is available
            if (!in_array($driver, PDO::getAvailableDrivers(), true)) {
                $drivers = implode(',', PDO::getAvailableDrivers()) ?: '(none)';
                throw new RuntimeException("Required PDO driver $driver is missing, Available drivers: $drivers");
            }

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ];

            if ($driver === 'mysql') {
                $options[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET SQL_MODE=ANSI_QUOTES;';
            }

            return new PDO(
                $app['config']['pdo']['dsn'],
                $app['config']['pdo']['user'],
                $app['config']['pdo']['pass'],
                $options
            );
        };

        $app[PdoRepository::class] = static function ($app) {
            return new PdoRepository($app['pdo'], $app['config']['pdo']['table']);
        };

        $app['searcher.pdo'] = static function ($app) {
            return new PdoSearcher($app[PdoRepository::class]);
        };

        $app['saver.pdo'] = static function ($app) {
            return new Saver\PdoSaver($app[PdoRepository::class]);
        };
    }

    private function storageDriverMongoDb(Container $app)
    {
        // NOTE: db.host, db.options, db.driverOptions, db.db are @deprecated and will be removed in the future
        $app['mongodb.database'] = static function ($app) {
            $config = $app['config'];
            $mongodb = $config['mongodb'] ?? [];

            return $config['db.db'] ?? $mongodb['database'] ?? 'xhgui';
        };

        $app[MongoDB::class] = static function ($app) {
            $database = $app['mongodb.database'];
            /** @var MongoClient $client */
            $client = $app[MongoClient::class];
            $mongoDB = $client->selectDb($database);
            $mongoDB->results->findOne();

            return $mongoDB;
        };

        $app[MongoClient::class] = static function ($app) {
            if (!class_exists(Manager::class)) {
                throw new RuntimeException('Required extension ext-mongodb missing');
            }

            $config = $app['config'];
            $mongodb = $config['mongodb'] ?? [];
            $options = $config['db.options'] ?? $mongodb['options'] ?? [];
            $driverOptions = $config['db.driverOptions'] ?? $mongodb['driverOptions'] ?? [];
            $server = $config['db.host'] ?? sprintf('mongodb://%s:%s', $mongodb['hostname'], $mongodb['port']);

            return new MongoClient($server, $options, $driverOptions);
        };

        $app['searcher.mongodb'] = static function ($app) {
            return new MongoSearcher($app[MongoDB::class]);
        };

        $app['saver.mongodb'] = static function ($app) {
            /** @var MongoDB $mongoDB */
            $mongoDB = $app[MongoDB::class];
            /** @var MongoCollection $collection */
            $collection = $mongoDB->results;

            return new Saver\MongoSaver($collection);
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
