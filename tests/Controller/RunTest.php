<?php

namespace XHGui\Test\Controller;

use Slim\Http\Environment;
use Slim\Http\Request;
use Slim\Http\Response;
use XHGui\Options\SearchOptions;
use XHGui\RequestProxy;
use XHGui\Test\TestCase;

class RunTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->env = Environment::mock([
            'SCRIPT_NAME' => 'index.php',
            'PATH_INFO' => '/',
        ]);
    }

    public function testIndexEmpty(): void
    {
        $env = [
            'SCRIPT_NAME' => 'index.php',
            'PATH_INFO' => '/',
        ];
        
        $request = $this->buildRequest($env);
        $response = new Response();
        
        $this->runs->index($request, $response);
        $result = $this->view->all();

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

    public function testIndexSortedWallTime(): void
    {
        $env = [
            'SCRIPT_NAME' => 'index.php',
            'PATH_INFO' => '/',
        ];
        $query = array('sort' => 'wt');
        
        $request = $this->buildRequest($env, array(), $query);
        $response = new Response();
        
        $this->runs->index($request, $response);
        
        $result = $this->view->all();
        $this->assertEquals('Longest wall time', $result['title']);
        $this->assertEquals('wt', $result['paging']['sort']);
    }

    public function testIndexSortedCpu(): void
    {
        $env = [
            'SCRIPT_NAME' => 'index.php',
            'PATH_INFO' => '/',
            'QUERY_STRING' => 'sort=cpu&direction=desc',
        ];
    
        $query = array('sort' => 'cpu', 'direction' => 'desc');
    
        $request = $this->buildRequest($env, array(), $query);
        $response = new Response();
    
        $this->runs->index($request, $response);
        $result = $this->view->all();
        $this->assertEquals('Most CPU time', $result['title']);
        $this->assertEquals('cpu', $result['paging']['sort']);
        $this->assertEquals('desc', $result['paging']['direction']);
    }

    public function testIndexWithSearch(): void
    {
        $env = [
            'SCRIPT_NAME' => 'index.php',
            'PATH_INFO' => '/',
            'QUERY_STRING' => 'sort=mu&direction=asc&url=index.php',
        ];
    
        $query = array('sort' => 'mu', 'direction' => 'asc', 'url' => 'index.php');
    
        $request = $this->buildRequest($env, array(), $query);
        $response = new Response();
    
        $this->runs->index($request, $response);
        $result = $this->view->all();
        $this->assertEquals('Highest memory use', $result['title']);
        $this->assertEquals('mu', $result['paging']['sort']);
        $this->assertEquals('asc', $result['paging']['direction']);
        $this->assertEquals(['url' => 'index.php'], $result['search']);
        $this->assertTrue($result['has_search']);
    }

    public function testUrl(): void
    {
        $this->skipIfPdo('getForUrl is not implemented');

        $env = [
            'SCRIPT_NAME' => 'index.php',
            'PATH_INFO' => '/url/view',
        ];
        
        $query = array('url' => '/tasks');

        $request = $this->buildRequest($env, array(), $query);
        
        $this->runs->url($request);

        $result = $this->view->all();
        $this->assertEquals('url.view', $result['base_url']);
        $this->assertEquals('/tasks', $result['url']);
        $this->assertArrayHasKey('chart_data', $result);
        $this->assertArrayHasKey('runs', $result);
    }

    public function testUrlWithSearch(): void
    {
        $this->markTestIncomplete('Not done');
    }

    public function testUrlWithSearchInterval(): void
    {
        $this->markTestIncomplete('Not done');
    }

    public function testCompareNoBase(): void
    {
        $this->markTestIncomplete('Not done');
    }

    public function testCompareWithBase(): void
    {
        $this->markTestIncomplete('Not done');
    }

    public function testCompareWithBaseAndHead(): void
    {
        $this->markTestIncomplete('Not done');
    }

    public function testSymbol(): void
    {
        $this->markTestIncomplete('Not done');
    }

    public function testCallgraph(): void
    {
        $this->searcher->truncate();
        $this->importFixture($this->saver);
        $env = [
            'SCRIPT_NAME' => 'index.php',
            'PATH_INFO' => '/',
            'QUERY_STRING' => 'id=aaaaaaaaaaaaaaaaaaaaaaaa',
        ];
    
        $query = array('id' => 'aaaaaaaaaaaaaaaaaaaaaaaa');
    
        $request = $this->buildRequest($env, array(), $query);
        $response = new Response();
        
        $this->runs->callgraph($request);
        $result = $this->view->all();
        $this->assertArrayHasKey('profile', $result);
        $this->assertArrayHasKey('date_format', $result);
        $this->assertArrayNotHasKey('callgraph', $result);
    }

    public function testCallgraphData(): void
    {
        $this->searcher->truncate();
        $this->importFixture($this->saver);
        $env = [
            'SCRIPT_NAME' => 'index.php',
            'PATH_INFO' => '/',
        ];
    
        $query = array('id' => 'aaaaaaaaaaaaaaaaaaaaaaaa');
    
        $request = $this->buildRequest($env, array(), $query);
        $response = new Response();
    
        $response = $this->runs->callgraphData($request, $response);

        $this->assertEquals('application/json', $response->getHeader('Content-Type')[0]);
        $this->assertStringStartsWith('{"', (string)$response->getBody());
    }

    public function testDeleteSubmit(): void
    {

        $this->skipIfPdo('Undefined index: page');
        $searcher = $this->searcher->truncate();
        $this->importFixture($this->saver);

        $env = [
            'REQUEST_METHOD' => 'POST',
            'SCRIPT_NAME' => 'index.php',
            'PATH_INFO' => '/run/delete',
            'CONTENT_TYPE' => 'application/json',
        ];
        
        $request = $this->buildRequest($env, array('id' => 'aaaaaaaaaaaaaaaaaaaaaaaa'));
        $response = new Response();
        
        
//        $app->expects($this->once())
//            ->method('urlFor')
//            ->with('home');

//        $response->expects($this->once())
//            ->method('withRedirect');

        $result = $searcher->getAll(new SearchOptions());
        $count = count($result['results']);
    
        $this->runs->deleteSubmit($request, $response);

        $result = $searcher->getAll(new SearchOptions());
        $this->assertCount($count - 1, $result['results']);
    }

    public function testDeleteAllSubmit(): void
    {

        $this->skipIfPdo('Undefined index: page');
        $this->searcher->truncate();
        $this->importFixture($this->saver);

        $env = [
          'SCRIPT_NAME' => 'index.php',
          'PATH_INFO' => '/run/delete_all',
        ];

//        $app = $this->getMockApp();
        
//        $app->expects($this->once())
//          ->method('urlFor')
//          ->with('home');

//        $app->expects($this->once())
//          ->method('redirect');

        $result = $this->searcher->getAll(new SearchOptions());
        $this->assertGreaterThan(0, count($result['results']));
    
        $request = $this->buildRequest($env);
        $response = new Response();
        
        $this->runs->deleteAllSubmit($request, $response);

        $result = $this->searcher->getAll(new SearchOptions());
        $this->assertCount(0, $result['results']);
    }

    public function testFilterCustomMethods(): void
    {
        $this->searcher->truncate();
        $this->importFixture($this->saver);

        $env = [
            'SCRIPT_NAME' => 'index.php',
            'PATH_INFO' => '/run/view',
        ];
    
        $query = array('id' => 'aaaaaaaaaaaaaaaaaaaaaaaa', 'filter' => 'main*,strpos()');
    
        $request = $this->buildRequest($env, array(), $query);
        $response = new Response();
        
        $this->runs->view($request, $response);
        $result = $this->view->all();

        $this->assertCount(1, $result['profile']);
    }

    public function testFilterCustomMethod(): void
    {
        $this->searcher->truncate();
        $this->importFixture($this->saver);

        $env = [
            'SCRIPT_NAME' => 'index.php',
            'PATH_INFO' => '/run/view',
            'QUERY_STRING' => 'id=aaaaaaaaaaaaaaaaaaaaaaad&filter=main*',
        ];
    
        $query = array('id' => 'aaaaaaaaaaaaaaaaaaaaaaaa', 'filter' => 'main');
    
        $request = $this->buildRequest($env, array(), $query);
        $response = new Response();
        
        $this->runs->view($request, $response);
        $result = $this->view->all();

        $this->assertCount(2, $result['profile']);
    }

    public function testFilterMethods(): void
    {
        $this->searcher->truncate();
        $this->importFixture($this->saver);

        $env = [
            'SCRIPT_NAME' => 'index.php',
            'PATH_INFO' => '/run/view',
            'QUERY_STRING' => 'id=aaaaaaaaaaaaaaaaaaaaaaad&filter=true',
        ];
    
        $query = array('id' => 'aaaaaaaaaaaaaaaaaaaaaaaa', 'filter' => 'true');
    
        $request = $this->buildRequest($env, array(), $query);
        $response = new Response();
    
        $this->runs->view($request, $response);
        $result = $this->view->all();

        $this->assertCount(2, $result['profile']);
    }
}
