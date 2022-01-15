<?php

namespace XHGui\Test\Controller;

use Slim\App;
use Slim\Http\Environment;
use Slim\Http\Request;
use XHGui\RequestProxy;
use XHGui\Test\TestCase;

class WatchTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->skipIfPdo('Watchers not implemented');

        Environment::mock([
           'SCRIPT_NAME' => 'index.php',
           'PATH_INFO' => '/watch',
        ]);
    }

    public function testGet(): void
    {
        $this->searcher->truncateWatches();
        $this->watches->get();
        $result = $this->view->all();
        $this->assertEquals([], $result['watched']);
    }

    public function testPostAdd(): void
    {

        $this->searcher->truncateWatches();
        
        $_POST = [
            'watch' => [
                ['name' => 'strlen'],
                ['name' => 'strpos'],
            ],
        ];
        
//        $app->expects($this->once())
//            ->method('urlFor')
//            ->with('watch.list');
//
//        $app->expects($this->once())
//            ->method('redirect');
    
        $env = [
            'SCRIPT_NAME' => 'index.php',
            'PATH_INFO' => '/watch',
            'Content_Type' => 'application/json'
        ];
    
        $request = $this->buildRequest($env, $_POST);
        
        $this->watches->post($request);
        $result = $this->searcher->getAllWatches();

        $this->assertCount(2, $result);
        $this->assertEquals('strlen', $result[0]['name']);
        $this->assertEquals('strpos', $result[1]['name']);
    }

    public function testPostModify(): void
    {
        $searcher = $this->searcher->truncateWatches();
        $searcher->saveWatch(['name' => 'strlen']);
        $saved = $searcher->getAllWatches();

        $_POST = [
            'watch' => [
                ['name' => 'strpos', '_id' => $saved[0]['_id']],
            ],
        ];

        $env = [
            'SCRIPT_NAME' => 'index.php',
            'PATH_INFO' => '/watch',
            'Content_Type' => 'application/json'
        ];

        $request = $this->buildRequest($env, $_POST);
        
        $this->watches->post($request);
        $result = $searcher->getAllWatches();

        $this->assertCount(1, $result);
        $this->assertEquals('strpos', $result[0]['name']);
    }

    public function testPostDelete(): void
    {
        $this->searcher->truncateWatches();
        $this->searcher->saveWatch(['name' => 'strlen']);
        $saved = $this->searcher->getAllWatches();

        $_POST = [
            'watch' => [
                ['removed' => 1, 'name' => 'strpos', '_id' => $saved[0]['_id']],
            ],
        ];

        $env = [
            'SCRIPT_NAME' => 'index.php',
            'PATH_INFO' => '/watch',
            'Content_Type' => 'application/json'
        ];

        $request = $this->buildRequest($env, $_POST);
        
        $this->watches->post($request);

        $result = $this->searcher->getAllWatches();

        $this->assertCount(0, $result);
    }
}
