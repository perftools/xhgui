<?php

namespace XHGui\Test;

use LazyProperty\LazyPropertiesTrait;
use MongoDB;
use Slim\App;
use Slim\Views\Twig;
use XHGui\Application;
use XHGui\Controller;
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
    /** @var Controller\RunController */
    protected $runs;
    /** @var App */
    protected $app;
    /** @var array */
    protected $config;
    /** @var SearcherInterface */
    protected $searcher;
    /** @var SaverInterface */
    protected $saver;
    /** @var Twig */
    protected $view;
    /** @var Controller\WatchController */
    protected $watches;

    protected function setupProperties(): void
    {
        $this->initLazyProperties([
            'di',
            'app',
            'config',
            'import',
            'mongo',
            'mongodb',
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
        $config = $di['config'];

        // Use a test databases
        // TODO: do the same for PDO. currently PDO uses DSN syntax and has too many variations
        $di['mongodb.database'] = 'test_xhgui';

        /** @var App $app */
        $app = $this->getMockBuilder(App::class)
            ->setMethods(['redirect', 'render', 'urlFor'])
            ->setConstructorArgs([$config])
            ->getMock();
        $di['app'] = $app;

        $view = $di['view'];
        $view->parserExtensions = [
            new TwigExtension($app),
        ];

        $app->view($view);
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

    protected function getView(): View
    {
        return $this->di['view'];
    }

    protected function getWatches()
    {
        return $this->di[Controller\WatchController::class];
    }
}
