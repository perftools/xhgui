<?php

namespace XHGui\Test\Controller;

use Slim\Environment;
use Slim\Slim as App;
use XHGui\Controller\WatchController;
use XHGui\Searcher\SearcherInterface;
use XHGui\ServiceContainer;
use XHGui\Test\TestCase;

class WatchTest extends TestCase
{
    /** @var WatchController */
    private $watches;
    /** @var App */
    private $app;
    /** @var SearcherInterface */
    private $searcher;

    public function setUp()
    {
        parent::setUp();
        Environment::mock([
           'SCRIPT_NAME' => 'index.php',
           'PATH_INFO' => '/watch',
        ]);

        $di = ServiceContainer::instance();
        $di['app'] = $this->getMockBuilder(App::class)
            ->setMethods(['redirect', 'render', 'urlFor'])
            ->setConstructorArgs([$di['config']])
            ->getMock();

        $this->watches = $di['watchController'];
        $this->app = $di['app'];
        $this->searcher = $di['searcher'];
        $this->searcher->truncateWatches();
    }

    public function testGet()
    {
        $this->watches->get();
        $result = $this->watches->templateVars();
        $this->assertEquals([], $result['watched']);
    }

    public function testPostAdd()
    {
        $_POST = [
            'watch' => [
                ['name' => 'strlen'],
                ['name' => 'strpos'],
            ],
        ];
        $this->app->expects($this->once())
            ->method('urlFor')
            ->with('watch.list');

        $this->app->expects($this->once())
            ->method('redirect');

        $this->watches->post($this->app->request());
        $result = $this->searcher->getAllWatches();

        $this->assertCount(2, $result);
        $this->assertEquals('strlen', $result[0]['name']);
        $this->assertEquals('strpos', $result[1]['name']);
    }

    public function testPostModify()
    {
        $this->searcher->saveWatch(['name' => 'strlen']);
        $saved = $this->searcher->getAllWatches();

        $_POST = [
            'watch' => [
                ['name' => 'strpos', '_id' => $saved[0]['_id']],
            ],
        ];
        $this->watches->post($this->app->request());
        $result = $this->searcher->getAllWatches();

        $this->assertCount(1, $result);
        $this->assertEquals('strpos', $result[0]['name']);
    }

    public function testPostDelete()
    {
        $this->searcher->saveWatch(['name' => 'strlen']);
        $saved = $this->searcher->getAllWatches();

        $_POST = [
            'watch' => [
                ['removed' => 1, 'name' => 'strpos', '_id' => $saved[0]['_id']],
            ],
        ];
        $this->watches->post($this->app->request());
        $result = $this->searcher->getAllWatches();

        $this->assertCount(0, $result);
    }
}
