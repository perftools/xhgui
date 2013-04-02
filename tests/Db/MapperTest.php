<?php

class Db_MapperTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->mapper = new Xhgui_Db_Mapper();
    }

    public function testConvertConditions()
    {
        $opts = array(
            'conditions' => array(
                'simple_url' => '/tasks',
                'url' => 'tasks',
                'date_start' => '2013-01-20',
                'date_end' => '2013-01-21',

            )
        );
        $result = $this->mapper->convert($opts);
        $expected = array(
            'meta.simple_url' => '/tasks',
            'meta.url' => array(
                '$regex' => 'tasks',
                '$options' => 'i'
            ),
            'meta.request_date' => array(
                '$gte' => '2013-01-20',
                '$lte' => '2013-01-21'
            ),
        );
        $this->assertEquals($expected, $result['conditions']);
    }

    public function testConditionsPartial()
    {
        $result = $this->mapper->convert(array(
            'conditions' => array(
                'date_start' => '2013-01-15',
            )
        ));
        $expected = array(
            'meta.request_date' => array(
                '$gte' => '2013-01-15',
            )
        );
        $this->assertEquals($expected, $result['conditions']);

        $result = $this->mapper->convert(array(
            'conditions' => array(
                'date_end' => '2013-01-20',
            )
        ));
        $expected = array(
            'meta.request_date' => array(
                '$lte' => '2013-01-20'
            )
        );
        $this->assertEquals($expected, $result['conditions']);

        $result = $this->mapper->convert(array(
            'conditions' => array(
                'date_start' => '2013-01-15',
                'date_end' => '2013-01-20',
                'url' => 'tasks'
            )
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
        $this->assertEquals($expected, $result['conditions']);
    }

    public function testConvertSort()
    {
        $options = array(
            'sort' => 'wt',
        );
        $result = $this->mapper->convert($options);
        $this->assertEquals(
            array('profile.main().wt' => -1),
            $result['sort']
        );

        $options = array(
            'sort' => 'wt',
            'direction' => 'asc'
        );
        $result = $this->mapper->convert($options);
        $this->assertEquals(
            array('profile.main().wt' => 1),
            $result['sort']
        );

        $options = array(
            'sort' => 'wt',
            'direction' => 'desc'
        );
        $result = $this->mapper->convert($options);
        $this->assertEquals(
            array('profile.main().wt' => -1),
            $result['sort']
        );

        $options = array(
            'sort' => 'wt',
            'direction' => 'farts'
        );
        $result = $this->mapper->convert($options);
        $this->assertEquals(
            array('profile.main().wt' => -1),
            $result['sort']
        );

        $options = array(
            'sort' => 'barf',
        );
        $result = $this->mapper->convert($options);
        $this->assertEquals(
            array('meta.SERVER.REQUEST_TIME' => -1),
            $result['sort']
        );
    }

    public function testConvertPerPage()
    {
        $options = array();
        $result = $this->mapper->convert($options);
        $this->assertEquals(25, $result['perPage']);

        $options = array(
            'perPage' => 1
        );
        $result = $this->mapper->convert($options);
        $this->assertEquals(1, $result['perPage']);
    }

}
