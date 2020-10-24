<?php

namespace XHGui\Test\Searcher;

use XHGui\Profile;
use XHGui\Searcher\MongoSearcher;
use XHGui\ServiceContainer;
use XHGui\Test\TestCase;

class MongoTest extends TestCase
{
    /**
     * @var MongoSearcher
     */
    private $mongo;

    public function setUp()
    {
        $di = ServiceContainer::instance();
        $this->mongo = $di['searcher.mongodb'];

        $di['db']->watches->drop();

        $this->loadFixture($di['saver.mongodb']);
    }

    public function testCustomQuery()
    {
        $conditions = ['meta.simple_url' => '/tasks'];

        $results = $this->mongo->query($conditions, 10);

        $this->assertTrue(is_array($results));
        $this->assertCount(3, $results);
    }

    public function testGetForUrl()
    {
        $options = [
            'perPage' => 1,
        ];
        $result = $this->mongo->getForUrl('/', $options);
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(2, $result['totalPages']);
        $this->assertEquals(1, $result['perPage']);

        $this->assertCount(1, $result['results']);
        $this->assertInstanceOf(Profile::class, $result['results'][0]);

        $result = $this->mongo->getForUrl('/not-there', $options);
        $this->assertCount(0, $result['results']);
    }

    public function testGetForUrlWithSearch()
    {
        $options = [
            'perPage' => 2,
        ];
        $search = [
            'date_start' => '2013-01-17',
            'date_end' => '2013-01-18',
        ];
        $result = $this->mongo->getForUrl('/', $options, $search);
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(1, $result['totalPages']);
        $this->assertEquals(2, $result['perPage']);
        $this->assertCount(1, $result['results']);

        $search = [
            'date_start' => '2013-01-01',
            'date_end' => '2013-01-02',
        ];
        $result = $this->mongo->getForUrl('/', $options, $search);
        $this->assertCount(0, $result['results']);
    }

    public function testGetAvgsForUrl()
    {
        $result = $this->mongo->getAvgsForUrl('/');
        $this->assertCount(2, $result);

        $this->assertArrayHasKey('avg_wt', $result[0]);
        $this->assertArrayHasKey('avg_cpu', $result[0]);
        $this->assertArrayHasKey('avg_mu', $result[0]);
        $this->assertArrayHasKey('avg_pmu', $result[0]);

        $this->assertEquals('2013-01-18', $result[0]['date']);
        $this->assertEquals('2013-01-19', $result[1]['date']);
    }

    public function testGetAvgsForUrlWithSearch()
    {
        $search = ['date_start' => '2013-01-18', 'date_end' => '2013-01-18'];
        $result = $this->mongo->getAvgsForUrl('/', $search);
        $this->assertCount(1, $result);

        $this->assertArrayHasKey('avg_wt', $result[0]);
        $this->assertArrayHasKey('avg_cpu', $result[0]);
        $this->assertArrayHasKey('avg_mu', $result[0]);
        $this->assertArrayHasKey('avg_pmu', $result[0]);

        $this->assertEquals('2013-01-18', $result[0]['date']);
    }

    public function testGetPercentileForUrlWithSearch()
    {
        $search = ['date_start' => '2013-01-18', 'date_end' => '2013-01-18'];
        $result = $this->mongo->getPercentileForUrl(20, '/', $search);
        $this->assertCount(1, $result);

        $this->assertArrayHasKey('wt', $result[0]);
        $this->assertArrayHasKey('cpu', $result[0]);
        $this->assertArrayHasKey('mu', $result[0]);
        $this->assertArrayHasKey('pmu', $result[0]);
    }

    public function testGetPercentileForUrlWithLimit()
    {
        $search = ['limit' => 'P1D'];
        $result = $this->mongo->getPercentileForUrl(20, '/', $search);
        $this->assertCount(0, $result);
    }

    public function testGetAllConditions()
    {
        $result = $this->mongo->getAll([
            'conditions' => [
                'date_start' => '2013-01-20',
                'date_end' => '2013-01-21',
                'url' => 'tasks',
            ],
        ]);
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(25, $result['perPage']);
        $this->assertEquals(1, $result['totalPages']);
        $this->assertCount(2, $result['results']);
    }

    public function testLatest()
    {
        $result = $this->mongo->latest();
        $this->assertInstanceOf(Profile::class, $result);
        $this->assertEquals('2013-01-21', $result->getDate()->format('Y-m-d'));
    }

    public function testSaveInsert()
    {
        $data = [
            'name' => 'strlen',
        ];
        $this->assertTrue($this->mongo->saveWatch($data));
        $this->assertCount(1, $this->mongo->getAllWatches());

        $data = [
            'name' => 'empty',
        ];
        $this->assertTrue($this->mongo->saveWatch($data));
        $this->assertCount(2, $this->mongo->getAllWatches());
    }

    public function testSaveUpdate()
    {
        $data = [
            'name' => 'strlen',
        ];
        $this->mongo->saveWatch($data);
        $result = $this->mongo->getAllWatches();

        $result[0]['name'] = 'strpos';
        $this->assertTrue($this->mongo->saveWatch($result[0]));
        $results = $this->mongo->getAllWatches();
        $this->assertCount(1, $results);
        $this->assertEquals('strpos', $results[0]['name']);
    }

    public function testSaveRemove()
    {
        $data = [
            'name' => 'strlen',
        ];
        $this->mongo->saveWatch($data);
        $result = $this->mongo->getAllWatches();

        $result[0]['removed'] = 1;
        $this->assertTrue($this->mongo->saveWatch($result[0]));
        $this->assertCount(0, $this->mongo->getAllWatches());
    }
}
