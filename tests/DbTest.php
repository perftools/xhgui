<?php
class DbTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Xhgui_Config::load(ROOT_DIR . '/config/config.php');
        $this->db = new Xhgui_Db(null, 'test_results');
        $this->db->truncate();
        $this->_loadFixture('tests/fixtures/results.json');
    }

    protected function _loadFixture($file)
    {
        $contents = file_get_contents($file);
        $data = json_decode($contents, true);
        foreach ($data as $record) {
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
        $result = $this->db->getForUrl('/', 1);
        $result = iterator_to_array($result);
        $this->assertCount(1, $result);

        $result = $this->db->getForUrl('/not-there', 1);
        $result = iterator_to_array($result);
        $this->assertCount(0, $result);
    }

}
