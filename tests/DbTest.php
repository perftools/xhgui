<?php
class DbTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Xhgui_Config::load(ROOT_DIR . '/config/config.php');
        $this->db = new Xhgui_Db(null, 'test_results');
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

}
