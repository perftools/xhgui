<?php

namespace XHGui\Test\Controller;

use Slim\Slim;
use Slim\Environment;
use XHGui\Test\TestCase;
use Xhgui_Controller_Import;
use Xhgui_Profile;
use Xhgui_ServiceContainer;

class ImportTest extends TestCase
{
    private $profiles;
    /** @var Xhgui_Controller_Import */
    private $import;

    public function setUp()
    {
        parent::setUp();
        Environment::mock([
            'SCRIPT_NAME' => 'index.php',
            'PATH_INFO' => '/'
        ]);

        $di = Xhgui_ServiceContainer::instance();
        $mock = $this->getMockBuilder(Slim::class)
            ->setMethods(['redirect', 'render', 'urlFor'])
            ->setConstructorArgs([$di['config']])
            ->getMock();

        $di['app'] = $di->share(static function ($c) use ($mock) {
            return $mock;
        });
        $this->import = $di['importController'];
        $this->app = $di['app'];

        $this->profiles = $di['searcher.mongo'];
        $this->profiles->truncate();
    }

    public function testImportSuccess()
    {
        $data = [
            'meta' => [
                'url' => '/things?key=value',
                'simple_url' => '/things',
                'get' => [],
                'env' => [],
                'SERVER' => ['REQUEST_TIME' => 1358787612],
                'request_date' => '2013-01-21',
                'request_ts' => ['sec' => 1358787612, 'usec' => 0],
                'request_ts_micro' => ['sec' => 1358787612, 'usec' => 123456]
            ],
            'profile' => [
                "main()" => [
                    "ct" => 1,
                    "wt" => 50139,
                    "cpu" => 49513,
                    "mu" => 3449360,
                    "pmu" => 3535120
                ]
            ]
        ];
        Environment::mock([
            'SCRIPT_NAME' => 'index.php',
            'PATH_INFO' => '/',
            'slim.input' => json_encode($data)
        ]);

        $before = $this->profiles->getForUrl('/things', []);
        $this->assertEmpty($before['results']);

        $this->import->import();

        $after = $this->profiles->getForUrl('/things', []);
        $this->assertNotEmpty($after['results']);
        $this->assertInstanceOf(Xhgui_Profile::class, $after['results'][0]);
    }
}
