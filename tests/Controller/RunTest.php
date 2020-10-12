<?php

namespace XHGui\Test\Controller;

use Slim\Environment;
use Slim\Slim as App;
use XHGui\Controller\ImportController;
use XHGui\Controller\RunController;
use XHGui\Saver\MongoSaver;
use XHGui\Searcher\MongoSearcher;
use XHGui\ServiceContainer;
use XHGui\Test\TestCase;

class RunTest extends TestCase
{
    /** @var RunController */
    private $runs;
    /** @var MongoSaver */
    private $saver;
    /** @var App */
    private $app;
    /** @var MongoSearcher */
    private $profiles;
    /** @var ImportController */
    private $import;

    public function setUp()
    {
        parent::setUp();
        Environment::mock([
            'SCRIPT_NAME' => 'index.php',
            'PATH_INFO' => '/',
        ]);

        $di = ServiceContainer::instance();
        $di['app'] = $this->getMockBuilder(App::class)
            ->setMethods(['redirect', 'render', 'urlFor'])
            ->setConstructorArgs([$di['config']])
            ->getMock();

        $this->import = $di['importController'];
        $this->runs = $di['runController'];
        $this->app = $di['app'];
        $this->profiles = $di['searcher'];
        $this->profiles->truncate();
        $this->saver = $di['saver'];
    }

    public function testIndexEmpty()
    {
        $this->runs->index($this->app->request(), $this->app->response());
        $result = $this->runs->templateVars();

        $this->assertEquals('Recent runs', $result['title']);
        $this->assertFalse($result['has_search'], 'No search being done.');
        $expected = [
            'total_pages' => 1,
            'page' => 1,
            'sort' => null,
            'direction' => 'desc',
        ];
        $this->assertEquals($expected, $result['paging']);
    }

    public function testIndexSortedWallTime()
    {
        Environment::mock([
            'SCRIPT_NAME' => 'index.php',
            'PATH_INFO' => '/',
            'QUERY_STRING' => 'sort=wt',
        ]);

        $this->runs->index($this->app->request(), $this->app->response());
        $result = $this->runs->templateVars();
        $this->assertEquals('Longest wall time', $result['title']);
        $this->assertEquals('wt', $result['paging']['sort']);
    }

    public function testIndexSortedCpu()
    {
        Environment::mock([
            'SCRIPT_NAME' => 'index.php',
            'PATH_INFO' => '/',
            'QUERY_STRING' => 'sort=cpu&direction=desc',
        ]);

        $this->runs->index($this->app->request(), $this->app->response());
        $result = $this->runs->templateVars();
        $this->assertEquals('Most CPU time', $result['title']);
        $this->assertEquals('cpu', $result['paging']['sort']);
        $this->assertEquals('desc', $result['paging']['direction']);
    }

    public function testIndexWithSearch()
    {
        Environment::mock([
            'SCRIPT_NAME' => 'index.php',
            'PATH_INFO' => '/',
            'QUERY_STRING' => 'sort=mu&direction=asc&url=index.php',
        ]);

        $this->runs->index($this->app->request(), $this->app->response());
        $result = $this->runs->templateVars();
        $this->assertEquals('Highest memory use', $result['title']);
        $this->assertEquals('mu', $result['paging']['sort']);
        $this->assertEquals('asc', $result['paging']['direction']);
        $this->assertEquals(['url' => 'index.php'], $result['search']);
        $this->assertTrue($result['has_search']);
    }

    public function testUrl()
    {
        Environment::mock([
            'SCRIPT_NAME' => 'index.php',
            'PATH_INFO' => '/url/view',
            'QUERY_STRING' => 'url=%2Ftasks',
        ]);

        $this->runs->url($this->app->request(), $this->app->response());

        $result = $this->runs->templateVars();
        $this->assertEquals('url.view', $result['base_url']);
        $this->assertEquals('/tasks', $result['url']);
        $this->assertArrayHasKey('chart_data', $result);
        $this->assertArrayHasKey('runs', $result);
    }

    public function testUrlWithSearch()
    {
        $this->markTestIncomplete('Not done');
    }

    public function testUrlWithSearchInterval()
    {
        $this->markTestIncomplete('Not done');
    }

    public function testCompareNoBase()
    {
        $this->markTestIncomplete('Not done');
    }

    public function testCompareWithBase()
    {
        $this->markTestIncomplete('Not done');
    }

    public function testCompareWithBaseAndHead()
    {
        $this->markTestIncomplete('Not done');
    }

    public function testSymbol()
    {
        $this->markTestIncomplete('Not done');
    }

    public function testCallgraph()
    {
        $this->loadFixture($this->saver, XHGUI_ROOT_DIR . '/tests/fixtures/results.json');
        Environment::mock([
            'SCRIPT_NAME' => 'index.php',
            'PATH_INFO' => '/',
            'QUERY_STRING' => 'id=aaaaaaaaaaaaaaaaaaaaaaaa',
        ]);

        $this->runs->callgraph($this->app->request());
        $result = $this->runs->templateVars();
        $this->assertArrayHasKey('profile', $result);
        $this->assertArrayHasKey('date_format', $result);
        $this->assertArrayNotHasKey('callgraph', $result);
    }

    public function testCallgraphData()
    {
        $this->loadFixture($this->saver, XHGUI_ROOT_DIR . '/tests/fixtures/results.json');
        Environment::mock([
            'SCRIPT_NAME' => 'index.php',
            'PATH_INFO' => '/',
            'QUERY_STRING' => 'id=aaaaaaaaaaaaaaaaaaaaaaaa',
        ]);

        $this->runs->callgraphData($this->app->request(), $this->app->response());
        $response = $this->app->response();

        $this->assertEquals('application/json', $response['Content-Type']);
        $this->assertStringStartsWith('{"', $response->body());
    }

    public function testDeleteSubmit()
    {
        $this->loadFixture($this->saver, XHGUI_ROOT_DIR . '/tests/fixtures/results.json');

        Environment::mock([
            'REQUEST_METHOD' => 'POST',
            'SCRIPT_NAME' => 'index.php',
            'PATH_INFO' => '/run/delete',
            'slim.request.form_hash' => [
                'id' => 'aaaaaaaaaaaaaaaaaaaaaaaa',
            ],
        ]);

        $this->app->expects($this->once())
            ->method('urlFor')
            ->with('home');

        $this->app->expects($this->once())
            ->method('redirect');

        $result = $this->profiles->getAll();
        $this->assertCount(5, $result['results']);

        $this->runs->deleteSubmit($this->app->request());

        $result = $this->profiles->getAll();
        $this->assertCount(4, $result['results']);
    }

    public function testDeleteAllSubmit()
    {
        $this->loadFixture($this->saver, XHGUI_ROOT_DIR . '/tests/fixtures/results.json');

        Environment::mock([
          'SCRIPT_NAME' => 'index.php',
          'PATH_INFO' => '/run/delete_all',
        ]);

        $this->app->expects($this->once())
          ->method('urlFor')
          ->with('home');

        $this->app->expects($this->once())
          ->method('redirect');

        $result = $this->profiles->getAll();
        $this->assertCount(5, $result['results']);

        $this->runs->deleteAllSubmit();

        $result = $this->profiles->getAll();
        $this->assertCount(0, $result['results']);
    }

    public function testFilterCustomMethods()
    {
        $this->loadFixture($this->saver, XHGUI_ROOT_DIR . '/tests/fixtures/results.json');

        Environment::mock([
            'SCRIPT_NAME' => 'index.php',
            'PATH_INFO' => '/run/view',
            'QUERY_STRING' => 'id=aaaaaaaaaaaaaaaaaaaaaaad&filter=main*,strpos()',
        ]);

        $this->runs->view($this->app->request(), $this->app->response());
        $result = $this->runs->templateVars();

        $this->assertCount(1, $result['profile']);
    }

    public function testFilterCustomMethod()
    {
        $this->loadFixture($this->saver, XHGUI_ROOT_DIR . '/tests/fixtures/results.json');

        Environment::mock([
            'SCRIPT_NAME' => 'index.php',
            'PATH_INFO' => '/run/view',
            'QUERY_STRING' => 'id=aaaaaaaaaaaaaaaaaaaaaaad&filter=main*',
        ]);

        $this->runs->view($this->app->request(), $this->app->response());
        $result = $this->runs->templateVars();

        $this->assertCount(2, $result['profile']);
    }

    public function testFilterMethods()
    {
        $this->loadFixture($this->saver, XHGUI_ROOT_DIR . '/tests/fixtures/results.json');

        Environment::mock([
            'SCRIPT_NAME' => 'index.php',
            'PATH_INFO' => '/run/view',
            'QUERY_STRING' => 'id=aaaaaaaaaaaaaaaaaaaaaaad&filter=true',
        ]);

        $this->runs->view($this->app->request(), $this->app->response());
        $result = $this->runs->templateVars();

        $this->assertCount(2, $result['profile']);
    }
}
