<?php

class MongoTest extends PHPUnit\Framework\TestCase
{
    /**
     * @var Xhgui_Searcher_Mongo
     */
    private $mongo;

    public function setUp()
    {
        $di = Xhgui_ServiceContainer::instance();
        $this->mongo = $di['searcher.mongo'];

        $di['db']->watches->drop();

        loadFixture($di['saver.mongo'], XHGUI_ROOT_DIR . '/tests/fixtures/results.json');
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
        $options = array(
            'perPage' => 1
        );
        $result = $this->mongo->getForUrl('/', $options);
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(2, $result['totalPages']);
        $this->assertEquals(1, $result['perPage']);

        $this->assertCount(1, $result['results']);
        $this->assertInstanceOf('Xhgui_Profile', $result['results'][0]);

        $result = $this->mongo->getForUrl('/not-there', $options);
        $this->assertCount(0, $result['results']);
    }

    public function testGetForUrlWithSearch()
    {
        $options = array(
            'perPage' => 2
        );
        $search = array(
            'date_start' => '2013-01-17',
            'date_end' => '2013-01-18',
        );
        $result = $this->mongo->getForUrl('/', $options, $search);
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(1, $result['totalPages']);
        $this->assertEquals(2, $result['perPage']);
        $this->assertCount(1, $result['results']);

        $search = array(
            'date_start' => '2013-01-01',
            'date_end' => '2013-01-02',
        );
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
        $search = array('date_start' => '2013-01-18', 'date_end' => '2013-01-18');
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
        $search = array('date_start' => '2013-01-18', 'date_end' => '2013-01-18');
        $result = $this->mongo->getPercentileForUrl(20, '/', $search);
        $this->assertCount(1, $result);

        $this->assertArrayHasKey('wt', $result[0]);
        $this->assertArrayHasKey('cpu', $result[0]);
        $this->assertArrayHasKey('mu', $result[0]);
        $this->assertArrayHasKey('pmu', $result[0]);
    }

    public function testGetPercentileForUrlWithLimit()
    {
        $search = array('limit' => 'P1D');
        $result = $this->mongo->getPercentileForUrl(20, '/', $search);
        $this->assertCount(0, $result);
    }

    public function testGetAllConditions()
    {
        $result = $this->mongo->getAll(array(
            'conditions' => array(
                'date_start' => '2013-01-20',
                'date_end' => '2013-01-21',
                'url' => 'tasks',
            )
        ));
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(25, $result['perPage']);
        $this->assertEquals(1, $result['totalPages']);
        $this->assertCount(2, $result['results']);
    }

    public function testLatest()
    {
        $result = $this->mongo->latest();
        $this->assertInstanceOf('Xhgui_Profile', $result);
        $this->assertEquals('2013-01-21', $result->getDate()->format('Y-m-d'));
    }

    public function testSaveInsert()
    {
        $data = array(
            'name' => 'strlen',
        );
        $this->assertTrue($this->mongo->saveWatch($data));
        $this->assertCount(1, $this->mongo->getAllWatches());

        $data = array(
            'name' => 'empty',
        );
        $this->assertTrue($this->mongo->saveWatch($data));
        $this->assertCount(2, $this->mongo->getAllWatches());
    }

    public function testSaveUpdate()
    {
        $data = array(
            'name' => 'strlen',
        );
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
        $data = array(
            'name' => 'strlen',
        );
        $this->mongo->saveWatch($data);
        $result = $this->mongo->getAllWatches();

        $result[0]['removed'] = 1;
        $this->assertTrue($this->mongo->saveWatch($result[0]));
        $this->assertCount(0, $this->mongo->getAllWatches());
    }
}
