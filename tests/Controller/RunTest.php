<?php

namespace XHGui\Test\Controller;

use Slim\Http\Environment;
use XHGui\Options\SearchOptions;
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
        $this->runs->index($this->request);
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
        $this->env = Environment::mock([
            'SCRIPT_NAME' => 'index.php',
            'PATH_INFO' => '/',
            'QUERY_STRING' => 'sort=wt',
        ]);

        $this->runs->index($this->request);
        $result = $this->view->all();
        $this->assertEquals('Longest wall time', $result['title']);
        $this->assertEquals('wt', $result['paging']['sort']);
    }

    public function testIndexSortedCpu(): void
    {
        $this->env = Environment::mock([
            'SCRIPT_NAME' => 'index.php',
            'PATH_INFO' => '/',
            'QUERY_STRING' => 'sort=cpu&direction=desc',
        ]);

        $this->runs->index($this->request);
        $result = $this->view->all();
        $this->assertEquals('Most CPU time', $result['title']);
        $this->assertEquals('cpu', $result['paging']['sort']);
        $this->assertEquals('desc', $result['paging']['direction']);
    }

    public function testIndexWithSearch(): void
    {
        $this->env = Environment::mock([
            'SCRIPT_NAME' => 'index.php',
            'PATH_INFO' => '/',
            'QUERY_STRING' => 'sort=mu&direction=asc&url=index.php',
        ]);

        $this->runs->index($this->request);
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

        $this->env = Environment::mock([
            'SCRIPT_NAME' => 'index.php',
            'PATH_INFO' => '/url/view',
            'QUERY_STRING' => 'url=%2Ftasks',
        ]);

        $this->runs->url($this->request);

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
        $this->env = Environment::mock([
            'SCRIPT_NAME' => 'index.php',
            'PATH_INFO' => '/',
            'QUERY_STRING' => 'id=aaaaaaaaaaaaaaaaaaaaaaaa',
        ]);

        $this->runs->callgraph($this->request);
        $result = $this->view->all();
        $this->assertArrayHasKey('profile', $result);
        $this->assertArrayHasKey('date_format', $result);
        $this->assertArrayNotHasKey('callgraph', $result);
    }

    public function testCallgraphData(): void
    {
        $this->searcher->truncate();
        $this->importFixture($this->saver);
        $this->env = Environment::mock([
            'SCRIPT_NAME' => 'index.php',
            'PATH_INFO' => '/',
            'QUERY_STRING' => 'id=aaaaaaaaaaaaaaaaaaaaaaaa',
        ]);

        $result = $this->runs->callgraphData($this->request);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('metric', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('nodes', $result);
        $this->assertArrayHasKey('links', $result);
    }

    public function testDeleteSubmit(): void
    {
        $this->skipIfPdo('Undefined index: page');
        $searcher = $this->searcher->truncate();
        $this->importFixture($this->saver);

        $this->env = Environment::mock([
            'REQUEST_METHOD' => 'POST',
            'SCRIPT_NAME' => 'index.php',
            'PATH_INFO' => '/run/delete',
        ]);

        $request = $this->createPostRequest(['id' => 'aaaaaaaaaaaaaaaaaaaaaaaa']);

        $result = $searcher->getAll(new SearchOptions());
        $count = count($result['results']);

        $this->runs->deleteSubmit($request);

        $result = $searcher->getAll(new SearchOptions());
        $this->assertCount($count - 1, $result['results']);
    }

    public function testDeleteAllSubmit(): void
    {
        $this->skipIfPdo('Undefined index: page');
        $this->searcher->truncate();
        $this->importFixture($this->saver);

        $this->env = Environment::mock([
          'SCRIPT_NAME' => 'index.php',
          'PATH_INFO' => '/run/delete_all',
        ]);

        $result = $this->searcher->getAll(new SearchOptions());
        $this->assertGreaterThan(0, count($result['results']));

        $this->runs->deleteAllSubmit();

        $result = $this->searcher->getAll(new SearchOptions());
        $this->assertCount(0, $result['results']);
    }

    public function testFilterCustomMethods(): void
    {
        $this->searcher->truncate();
        $this->importFixture($this->saver);

        $this->env = Environment::mock([
            'SCRIPT_NAME' => 'index.php',
            'PATH_INFO' => '/run/view',
            'QUERY_STRING' => 'id=aaaaaaaaaaaaaaaaaaaaaaad&filter=main*,strpos()',
        ]);

        $this->runs->view($this->request);
        $result = $this->view->all();

        $this->assertCount(1, $result['profile']);
    }

    public function testFilterCustomMethod(): void
    {
        $this->searcher->truncate();
        $this->importFixture($this->saver);

        $this->env = Environment::mock([
            'SCRIPT_NAME' => 'index.php',
            'PATH_INFO' => '/run/view',
            'QUERY_STRING' => 'id=aaaaaaaaaaaaaaaaaaaaaaad&filter=main*',
        ]);

        $this->runs->view($this->request);
        $result = $this->view->all();

        $this->assertCount(2, $result['profile']);
    }

    public function testFilterMethods(): void
    {
        $this->searcher->truncate();
        $this->importFixture($this->saver);

        $this->env = Environment::mock([
            'SCRIPT_NAME' => 'index.php',
            'PATH_INFO' => '/run/view',
            'QUERY_STRING' => 'id=aaaaaaaaaaaaaaaaaaaaaaad&filter=true',
        ]);

        $this->runs->view($this->request);
        $result = $this->view->all();

        $this->assertCount(2, $result['profile']);
    }
}
