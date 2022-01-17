<?php

namespace XHGui\Test;

use LazyProperty\LazyPropertiesTrait;
use MongoDB;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Slim\App;
use Slim\Http\Environment;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Views\Twig;
use XHGui\Application;
use XHGui\Controller;
use XHGui\RequestProxy;
use XHGui\Saver\SaverInterface;
use XHGui\Searcher\MongoSearcher;
use XHGui\Searcher\SearcherInterface;
use XHGui\Twig\TwigExtension;

trait LazyContainerProperties
{
    use LazyPropertiesTrait;

    /** @var Application */
    protected $di;
    /** @var Controller\ImportController */
    protected $import;
    /** @var MongoSearcher */
    protected $mongo;
    /** @var MongoDB */
    protected $mongodb;
    /** @var RequestProxy */
    protected $request;
    /** @var Response */
    protected $response;
    /** @var Controller\RunController */
    protected $runs;
    /** @var App */
    protected $app;
    /** @var array */
    protected $config;
    /** @var Environment */
    protected $env;
    /** @var TwigExtension */
    protected $ext;
    /** @var SearcherInterface */
    protected $searcher;
    /** @var SaverInterface */
    protected $saver;
    /** @var TwigView */
    protected $view;
    /** @var Controller\WatchController */
    protected $watches;

    protected function setupProperties(): void
    {
        $this->initLazyProperties([
            'di',
            'app',
            'config',
            'env',
            'ext',
            'import',
            'mongo',
            'mongodb',
            'request',
            'response',
            'runs',
            'saver',
            'searcher',
            'view',
            'watches',
        ]);
    }

    protected function getDi()
    {
        $di = new Application();

        // Use a test databases
        // TODO: do the same for PDO. currently PDO uses DSN syntax and has too many variations
        $di['mongodb.database'] = 'test_xhgui';

        /** @var \Slim\Container $container */
        $container = $di['app']->getContainer();
        $container->register(new class($this) implements ServiceProviderInterface {
            private $ctx;

            public function __construct($ctx)
            {
                $this->ctx = $ctx;
            }

            public function register(Container $container): void
            {
                $container['view.class'] = TwigView::class;
                $container['flash.storage'] = [];
                $container['environment'] = function () {
                    return $this->ctx->getEnv();
                };
            }
        });

        $di->boot();

        return $di;
    }

    protected function getApp()
    {
        return $this->di['app'];
    }

    protected function getConfig()
    {
        return $this->di['config'];
    }

    public function getEnv()
    {
        return $this->env ?? $this->env = Environment::mock();
    }

    protected function getExt()
    {
        return $this->app->getContainer()[TwigExtension::class];
    }

    protected function getImport()
    {
        return $this->di[Controller\ImportController::class];
    }

    protected function getMongo()
    {
        return $this->di['searcher.mongodb'];
    }

    protected function getMongoDb()
    {
        return $this->di[MongoDB::class];
    }

    protected function getRequest(): RequestProxy
    {
        return new RequestProxy(Request::createFromEnvironment($this->env));
    }

    protected function getResponse(): Response
    {
        return $this->di['app']->getContainer()->get('response');
    }

    protected function getSearcher()
    {
        return $this->di['searcher'];
    }

    protected function getRuns()
    {
        return $this->di[Controller\RunController::class];
    }

    protected function getSaver()
    {
        return $this->di['saver'];
    }

    protected function getView(): Twig
    {
        return $this->di['app']->getContainer()->get('view');
    }

    protected function getWatches()
    {
        return $this->di[Controller\WatchController::class];
    }
}
