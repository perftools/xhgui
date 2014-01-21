<?php
class ProfilesTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $di = Xhgui_ServiceContainer::instance();
        $this->profiles = $di['profiles'];
        loadFixture($this->profiles, 'tests/fixtures/results.json');
    }

    public function testPagination()
    {
        $options = array(
            'page' => 1,
            'sort' => 'wt',
        );
        $result = $this->profiles->paginate($options);
        $this->assertEquals(25, $result['perPage'], 'default works');
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(
            array('profile.main().wt' => -1),
            $result['sort']
        );
    }

    public function testPaginateInvalidSort()
    {
        $options = array(
            'page' => 1,
            'sort' => 'barf',
        );
        $result = $this->profiles->paginate($options);
        $this->assertEquals(
            array('meta.SERVER.REQUEST_TIME' => -1),
            $result['sort']
        );
    }

    public function testPaginateOutOfRangePage()
    {
        $options = array(
            'page' => 9000,
            'sort' => 'barf',
        );
        $result = $this->profiles->paginate($options);
        $this->assertEquals(1, $result['page']);
    }

    public function testGetForUrl()
    {
        $options = array(
            'perPage' => 1
        );
        $result = $this->profiles->getForUrl('/', $options);
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(2, $result['totalPages']);
        $this->assertEquals(1, $result['perPage']);

        $this->assertCount(1, $result['results']);
        $this->assertInstanceOf('Xhgui_Profile', $result['results'][0]);

        $result = $this->profiles->getForUrl('/not-there', $options);
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
        $result = $this->profiles->getForUrl('/', $options, $search);
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(1, $result['totalPages']);
        $this->assertEquals(2, $result['perPage']);
        $this->assertCount(1, $result['results']);

        $search = array(
            'date_start' => '2013-01-01',
            'date_end' => '2013-01-02',
        );
        $result = $this->profiles->getForUrl('/', $options, $search);
        $this->assertCount(0, $result['results']);
    }

    public function testGetAvgsForUrl()
    {
        $result = $this->profiles->getAvgsForUrl('/');
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
        $result = $this->profiles->getAvgsForUrl('/', $search);
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
        $result = $this->profiles->getPercentileForUrl(20, '/', $search);
        $this->assertCount(1, $result);

        $this->assertArrayHasKey('wt', $result[0]);
        $this->assertArrayHasKey('cpu', $result[0]);
        $this->assertArrayHasKey('mu', $result[0]);
        $this->assertArrayHasKey('pmu', $result[0]);
    }

    public function testGetPercentileForUrlWithLimit()
    {
        $search = array('limit' => 'P1D');
        $result = $this->profiles->getPercentileForUrl(20, '/', $search);
        $this->assertCount(0, $result);
    }

    public function testGetAllConditions()
    {
        $result = $this->profiles->getAll(array(
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
        $result = $this->profiles->latest();
        $this->assertInstanceOf('Xhgui_Profile', $result);
        $this->assertEquals('2013-01-21', $result->getDate()->format('Y-m-d'));
    }

}
