<?php

namespace XHGui\Test\Controller;

use Slim\Http\Environment;
use XHGui\Test\TestCase;

class WatchTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->skipIfPdo('Watchers not implemented');

        $this->env = Environment::mock([
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
        $request = $this->createPostRequest([
            'watch' => [
                ['name' => 'strlen'],
                ['name' => 'strpos'],
            ],
        ]);

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

        $request = $this->createPostRequest([
            'watch' => [
                ['name' => 'strpos', '_id' => $saved[0]['_id']],
            ],
        ]);
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

        $request = $this->createPostRequest([
            'watch' => [
                ['removed' => 1, 'name' => 'strpos', '_id' => $saved[0]['_id']],
            ],
        ]);

        $this->watches->post($request);
        $result = $this->searcher->getAllWatches();

        $this->assertCount(0, $result);
    }
}
