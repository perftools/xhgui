<?php
class DbTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Xhgui_Config::load(XHGUI_ROOT_DIR . '/config/config.php');
        $this->db = new Xhgui_Db(null, 'test_results');
        $this->db->truncate();
        $this->_loadFixture('tests/fixtures/results.json');
    }

    protected function _loadFixture($file)
    {
        $contents = file_get_contents($file);
        $data = json_decode($contents, true);
        foreach ($data as $record) {
            if (isset($record['meta']['request_time'])) {
                $time = strtotime($record['meta']['request_time']);
                $record['meta']['request_time'] = new MongoDate($time);
            }
            $this->db->insert($record);
        }
    }

    public function testPagination()
    {
        $options = array(
            'page' => 1,
            'sort' => 'wt',
        );
        $result = $this->db->pagination($options);
        $this->assertEquals(25, $result['perPage'], 'default works');
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(
            array('profile.main().wt' => -1),
            $result['sort']
        );
    }

    public function testPaginationInvalidSort()
    {
        $options = array(
            'page' => 1,
            'sort' => 'barf',
        );
        $result = $this->db->pagination($options);
        $this->assertEquals(
            array('meta.SERVER.REQUEST_TIME' => -1),
            $result['sort']
        );
    }

    public function testPaginationOutOfRangePage()
    {
        $options = array(
            'page' => 9000,
            'sort' => 'barf',
        );
        $result = $this->db->pagination($options);
        $this->assertEquals(1, $result['page']);
    }

    public function testGetForUrl()
    {
        $options = array(
            'perPage' => 1
        );
        $result = $this->db->getForUrl('/', $options);
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(2, $result['totalPages']);
        $this->assertEquals(1, $result['perPage']);

        $result = iterator_to_array($result['results']);
        $this->assertCount(1, $result);

        $result = $this->db->getForUrl('/not-there', $options);
        $result = iterator_to_array($result['results']);
        $this->assertCount(0, $result);
    }

    public function testGetAvgsForUrl()
    {
        $result = $this->db->getAvgsForUrl('/');
        $this->assertCount(2, $result);

        $this->assertArrayHasKey('avg_wt', $result[0]);
        $this->assertArrayHasKey('avg_cpu', $result[0]);
        $this->assertArrayHasKey('avg_mu', $result[0]);
        $this->assertArrayHasKey('avg_pmu', $result[0]);

        $this->assertEquals('2013-01-18', $result[0]['date']);
        $this->assertEquals('2013-01-19', $result[1]['date']);
    }

    public function testConvertConditions()
    {
        $result = $this->db->convertConditions(array(
            'date_start' => '2013-01-15',
        ));
        $expected = array(
            'meta.request_date' => array(
                '$gte' => '2013-01-15',
            )
        );
        $this->assertEquals($expected, $result);

        $result = $this->db->convertConditions(array(
            'date_end' => '2013-01-20',
        ));
        $expected = array(
            'meta.request_date' => array(
                '$lte' => '2013-01-20'
            )
        );
        $this->assertEquals($expected, $result);

        $result = $this->db->convertConditions(array(
            'date_start' => '2013-01-15',
            'date_end' => '2013-01-20',
            'url' => 'tasks'
        ));
        $expected = array(
            'meta.url' => array(
                '$regex' => 'tasks',
                '$options' => 'i'
            ),
            'meta.request_date' => array(
                '$gte' => '2013-01-15',
                '$lte' => '2013-01-20'
            )
        );
        $this->assertEquals($expected, $result);
    }

    public function testGetAllConditions()
    {
        $result = $this->db->getAll(array(
            'search' => array(
                'date_start' => '2013-01-20',
                'date_end' => '2013-01-21',
                'url' => 'tasks',
            )
        ));
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(25, $result['perPage']);
        $this->assertEquals(1, $result['totalPages']);
        $rows = iterator_to_array($result['results']);
        $this->assertCount(2, $rows);
    }

}
