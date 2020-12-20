<?php

namespace XHGui\Test\Controller;

use Slim\Environment;
use XHGui\Profile;
use XHGui\Test\LazyContainerProperties;
use XHGui\Test\TestCase;

class ImportTest extends TestCase
{
    use LazyContainerProperties;

    public function setUp()
    {
        parent::setUp();
        $this->setupProperties();

        Environment::mock([
            'SCRIPT_NAME' => 'index.php',
            'PATH_INFO' => '/',
        ]);

        $this->searcher->truncate();
    }

    public function testImportSuccess()
    {
        $this->skipIfPdo('getForUrl not implemented');
        $data = [
            'meta' => [
                'url' => '/things?key=value',
                'simple_url' => '/things',
                'get' => [],
                'env' => [],
                'SERVER' => ['REQUEST_TIME' => 1358787612],
                'request_ts_micro' => ['sec' => 1358787612, 'usec' => 123456],
            ],
            'profile' => [
                'main()' => [
                    'ct' => 1,
                    'wt' => 50139,
                    'cpu' => 49513,
                    'mu' => 3449360,
                    'pmu' => 3535120,
                ],
            ],
        ];
        Environment::mock([
            'SCRIPT_NAME' => 'index.php',
            'PATH_INFO' => '/',
            'slim.input' => json_encode($data),
        ]);

        $before = $this->searcher->getForUrl('/things', []);
        $this->assertEmpty($before['results']);

        $this->import->import($this->app->request(), $this->app->response());

        $after = $this->searcher->getForUrl('/things', []);
        $this->assertNotEmpty($after['results']);
        $this->assertInstanceOf(Profile::class, $after['results'][0]);
    }
}
