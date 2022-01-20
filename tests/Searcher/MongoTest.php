<?php

namespace XHGui\Test\Searcher;

use XHGui\Options\SearchOptions;
use XHGui\Profile;
use XHGui\Test\MongoHelper;
use XHGui\Test\TestCase;

class MongoTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->skipIfPdo('This is MongoDB test');
        $this->mongodb->watches->drop();
        $this->importFixture($this->di['saver.mongodb']);
    }

    public function testCustomQuery(): void
    {
        $conditions = ['meta.simple_url' => '/tasks'];

        $results = $this->mongo->query($conditions, 10);

        $this->assertTrue(is_array($results));
        $this->assertCount(3, $results);
    }

    public function testGetForUrl(): void
    {
        $options = [
            'perPage' => 1,
        ];
        $result = $this->mongo->getForUrl('/', $options);
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(4, $result['totalPages']);
        $this->assertEquals(1, $result['perPage']);

        $this->assertCount(1, $result['results']);
        $this->assertInstanceOf(Profile::class, $result['results'][0]);

        $result = $this->mongo->getForUrl('/not-there', $options);
        $this->assertCount(0, $result['results']);
    }

    public function testGetForUrlWithSearch(): void
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

    public function testGetAvgsForUrl(): void
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

    public function testGetAvgsForUrlWithSearch(): void
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

    public function testGetPercentileForUrlWithSearch(): void
    {
        $search = ['date_start' => '2013-01-18', 'date_end' => '2013-01-18'];
        $result = $this->mongo->getPercentileForUrl(20, '/', $search);
        $this->assertCount(1, $result);

        $this->assertArrayHasKey('wt', $result[0]);
        $this->assertArrayHasKey('cpu', $result[0]);
        $this->assertArrayHasKey('mu', $result[0]);
        $this->assertArrayHasKey('pmu', $result[0]);
    }

    public function testGetPercentileForUrlWithLimit(): void
    {
        $search = ['limit' => 'P1D'];
        $result = $this->mongo->getPercentileForUrl(20, '/', $search);
        $this->assertCount(0, $result);
    }

    public function testGetAllConditions(): void
    {
        $result = $this->mongo->getAll(new SearchOptions([
            'conditions' => [
                'date_start' => '2013-01-20',
                'date_end' => '2013-01-21',
                'url' => 'tasks',
            ],
        ]));
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(25, $result['perPage']);
        $this->assertEquals(1, $result['totalPages']);
        $this->assertCount(2, $result['results']);
    }

    public function testLatest(): void
    {
        $result = $this->mongo->latest();
        $this->assertInstanceOf(Profile::class, $result);
        $this->assertEquals('2020-04-18', $result->getDate()->format('Y-m-d'));
    }

    public function testSaveInsert(): void
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

    public function testSaveUpdate(): void
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

    public function testSaveRemove(): void
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

    public function testTruncateResultsPreserveIndexes(): void
    {
        $helper = new MongoHelper($this->mongodb);

        // dropping "results" collection using raw client
        // (indexes are lost)
        $helper->dropCollection('results');

        // recreating collection "results" with indexes
        $expectedIndexes = [
            [['_id' => 1], ['name' => '_id_']],
            [['meta.SERVER.REQUEST_TIME' => -1], ['name' => 'meta_srv_req_t']],
            [['profile.main().wt' => -1], ['name' => 'profile_wt']],
            [['profile.main().mu' => -1], ['name' => 'profile_mu']],
            [['profile.main().cpu' => -1], ['name' => 'profile_cpu']],
            [['meta.url' => 1], ['name' => 'meta_url']],
            [['meta.simple_url' => 1], ['name' => 'simple_url']],
            [['meta.request_ts' => 1], ['name' => 'req_ts', 'expireAfterSeconds' => 432000]],
        ];
        $helper->createCollection('results', $expectedIndexes);

        $this->importFixture($this->saver);

        $result = $this->mongo->getAll(new SearchOptions());
        $this->assertGreaterThan(0, count($result['results']));

        $this->mongo->truncate();

        $result = $this->mongo->getAll(new SearchOptions());
        $this->assertEmpty($result['results']);

        // assert that all indexes are intact after truncating
        // compare result against expected indexes
        foreach ($helper->getIndexes('results') as [$index, $name, $keys, $options]) {
            $this->assertEquals($keys, $index);
            $this->assertEquals($options['name'], $name);

            if ($name === 'meta.request_ts') {
                $this->assertArrayHasKey('expireAfterSeconds', $options);
                $this->assertEquals(432000, $options['expireAfterSeconds']);
            }
        }
    }

    public function testTruncateWatchesPreserveIndexes(): void
    {
        $helper = new MongoHelper($this->mongodb);

        // dropping "watches" collection using raw client
        // (indexes are lost)
        $helper->dropCollection('watches');

        // recreating collection "watches" with indexes
        $expectedIndexes = [
            [['_id' => 1], ['name' => '_id_']],
            [['name' => -1], ['name' => 'test_name']],
        ];
        $helper->createCollection('watches', $expectedIndexes);

        $this->searcher->saveWatch(['name' => 'strlen']);

        $result = $this->searcher->getAllWatches();
        $this->assertCount(1, $result);

        $this->mongo->truncateWatches();

        $result = $this->searcher->getAllWatches();
        $this->assertEmpty($result);

        // compare result against expected indexes
        foreach ($helper->getIndexes('watches') as [$index, $name, $keys, $options]) {
            $this->assertEquals($keys, $index);
            $this->assertEquals($options['name'], $name);
        }
    }

    public function testGetAllServerNames(): void
    {
        $result = $this->mongo->getAllServerNames();
        $this->assertCount(2, $result);
        $this->assertContains('localhost', $result);
        $this->assertContains('foo', $result);
    }
}
