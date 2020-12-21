<?php

namespace XHGui\Test;

use LazyProperty\LazyPropertiesTrait;
use Slim\Slim as App;
use XHGui\Controller\ImportController;
use XHGui\Controller\RunController;
use XHGui\Controller\WatchController;
use XHGui\Saver\MongoSaver;
use XHGui\Searcher\MongoSearcher;
use XHGui\Searcher\SearcherInterface;
use XHGui\ServiceContainer;

trait LazyContainerProperties
{
    use LazyPropertiesTrait;

    /** @var ServiceContainer */
    protected $di;
    /** @var ImportController */
    protected $import;
    /** @var MongoSearcher */
    private $mongo;
    /** @var RunController */
    protected $runs;
    /** @var App */
    protected $app;
    /** @var SearcherInterface */
    protected $searcher;
    /** @var MongoSaver */
    protected $saver;
    /** @var WatchController */
    protected $watches;

    protected function setupProperties()
    {
        $this->initLazyProperties([
            'di',
            'app',
            'import',
            'mongo',
            'runs',
            'saver',
            'searcher',
            'watches',
        ]);
    }

    protected function getDi()
    {
        $di = ServiceContainer::instance();
        $di['app'] = $this->getMockBuilder(App::class)
            ->setMethods(['redirect', 'render', 'urlFor'])
            ->setConstructorArgs([$di['config']])
            ->getMock();

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

    protected function getWatches()
    {
        return $this->di['watchController'];
    }
}
