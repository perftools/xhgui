<?php

namespace XHGui\Test\Controller;

use Slim\Http\Environment;
use Slim\Http\Request;
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

        $this->markTestSkipped('Replacement for $this->app->expects needed');

        $this->searcher->truncateWatches();
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

    public function testPostModify(): void
    {
        $searcher = $this->searcher->truncateWatches();
        $searcher->saveWatch(['name' => 'strlen']);
        $saved = $searcher->getAllWatches();

        $_P = [
            'watch' => [
                ['name' => 'strpos', '_id' => $saved[0]['_id']],
            ],
        ];

        $env = Environment::mock([
            'SCRIPT_NAME' => 'index.php',
            'PATH_INFO' => '/watch',
            'Content_Type' => 'application/json'
        ]);

        $request = Request::createFromEnvironment($env);

        $post_body = json_encode($_P);

        $stream = $request->getBody();
        $stream->write($post_body);
        $stream->rewind();

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

//        $_POST = [
//            'watch' => [
//                ['removed' => 1, 'name' => 'strpos', '_id' => $saved[0]['_id']],
//            ],
//        ];
//        $this->watches->post($this->app->request());

        $_P = [
            'watch' => [
                ['removed' => 1, 'name' => 'strpos', '_id' => $saved[0]['_id']],
            ],
        ];

        $env = Environment::mock([
            'SCRIPT_NAME' => 'index.php',
            'PATH_INFO' => '/watch',
            'Content_Type' => 'application/json'
        ]);

        $request = Request::createFromEnvironment($env);

        $post_body = json_encode($_P);

        $stream = $request->getBody();
        $stream->write($post_body);
        $stream->rewind();
        $this->watches->post($request);

        $result = $this->searcher->getAllWatches();

        $this->assertCount(0, $result);
    }
}
