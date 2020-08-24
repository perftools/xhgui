<?php

namespace XHGui\Test\Controller;

use Slim\Environment;
use XHGui\Test\TestCase;
use Xhgui_Controller_Watch;
use Xhgui_Searcher_Interface;
use Xhgui_ServiceContainer;
use Slim\Slim;

class WatchTest extends TestCase
{
    /** @var Xhgui_Controller_Watch */
    private $watches;
    /** @var Slim */
    private $app;
    /** @var Xhgui_Searcher_Interface */
    private $searcher;

    public function setUp()
    {
        parent::setUp();
        Environment::mock([
           'SCRIPT_NAME' => 'index.php',
           'PATH_INFO' => '/watch'
        ]);
        $di = Xhgui_ServiceContainer::instance();
        unset($di['app']);

        $mock = $this->getMockBuilder(Slim::class)
            ->setMethods(['redirect', 'render', 'urlFor'])
            ->setConstructorArgs([$di['config']])
            ->getMock();
        $di['app'] = $di->share(function ($c) use ($mock) {
            return $mock;
        });
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
                ['name' => 'strpos']
            ]
        ];
        $this->app->expects($this->once())
            ->method('urlFor')
            ->with('watch.list');

        $this->app->expects($this->once())
            ->method('redirect');

        $this->watches->post();
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
                ['name' => 'strpos', '_id' => $saved[0]['_id']]
            ]
        ];
        $this->watches->post();
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
                ['removed' => 1, 'name' => 'strpos', '_id' => $saved[0]['_id']]
            ]
        ];
        $this->watches->post();
        $result = $this->searcher->getAllWatches();

        $this->assertCount(0, $result);
    }

}
