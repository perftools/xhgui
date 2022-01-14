<?php

namespace XHGui\Test\Controller;

use Slim\Http\Environment;
use Slim\Http\Request;
use Slim\Http\Response;
use XHGui\Profile;
use XHGui\Test\TestCase;

class ImportTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Environment::mock([
            'SCRIPT_NAME' => 'index.php',
            'PATH_INFO' => '/',
        ]);
    }

    public function testImportSuccess(): void
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

        $request = Request::createFromEnvironment($this->env);

        $body = $request;
        $stream = $body->getBody();
        $stream->write(json_encode($data));
        $stream->rewind();

        $response = new Response();

        $searcher = $this->searcher->truncate();
        $before = $searcher->getForUrl('/things', []);
        $this->assertEmpty($before['results']);

        $this->import->import($request, $response);

        $after = $searcher->getForUrl('/things', []);
        $this->assertNotEmpty($after['results']);
        $this->assertInstanceOf(Profile::class, $after['results'][0]);
    }
}
