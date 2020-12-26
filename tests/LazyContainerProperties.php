<?php

namespace XHGui\Test;

use LazyProperty\LazyPropertiesTrait;
use MongoDB;
use Slim\Slim as App;
use Slim\View;
use XHGui\Controller\ImportController;
use XHGui\Controller\RunController;
use XHGui\Controller\WatchController;
use XHGui\Saver\MongoSaver;
use XHGui\Searcher\MongoSearcher;
use XHGui\Searcher\SearcherInterface;
use XHGui\ServiceContainer;
use XHGui\Twig\TwigExtension;

trait LazyContainerProperties
{
    use LazyPropertiesTrait;

    /** @var ServiceContainer */
    protected $di;
    /** @var ImportController */
    protected $import;
    /** @var MongoSearcher */
    private $mongo;
    /** @var MongoDB */
    protected $mongodb;
    /** @var RunController */
    protected $runs;
    /** @var App */
    protected $app;
    /** @var SearcherInterface */
    protected $searcher;
    /** @var MongoSaver */
    protected $saver;
    /** @var View */
    protected $view;
    /** @var WatchController */
    protected $watches;

    protected function setupProperties(): void
    {
        $this->initLazyProperties([
            'di',
            'app',
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
        $di = new ServiceContainer();
        $config = $di['config'];

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

    protected function getImport()
    {
        return $this->di['importController'];
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
        return $this->di['runController'];
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
        return $this->di['watchController'];
    }
}
