<?php
use Slim\Environment;

class Controller_ImportTest extends PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        parent::setUp();
        Environment::mock(array(
            'SCRIPT_NAME' => 'index.php',
            'PATH_INFO' => '/'
        ));

        $di = Xhgui_ServiceContainer::instance();
        $mock = $this->getMockBuilder('Slim\Slim')
            ->setMethods(array('redirect', 'render', 'urlFor'))
            ->setConstructorArgs(array($di['config']))
            ->getMock();

        $di['app'] = $di->share(function ($c) use ($mock) {
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
        Environment::mock(array(
            'SCRIPT_NAME' => 'index.php',
            'PATH_INFO' => '/',
            'slim.input' => json_encode($data)
        ));

        $before = $this->profiles->getForUrl('/things', []);
        $this->assertEmpty($before['results']);

        $this->import->import();

        $after = $this->profiles->getForUrl('/things', []);
        $this->assertNotEmpty($after['results']);
        $this->assertInstanceOf(Xhgui_Profile::class, $after['results'][0]);
    }
}
