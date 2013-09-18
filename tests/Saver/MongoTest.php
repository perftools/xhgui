<?php

class Saver_MongoTest extends PHPunit_Framework_TestCase
{
    public function testSave()
    {
        $data = array(
            'meta' => array(
                'some_meta_data'
            ),
            'profile' => array(
                array('symbols'=>array())
            )
        );

        $profiles = $this->getMockBuilder('Xhgui_Profiles')
                ->disableOriginalConstructor()
                ->getMock();
        $profiles->expects($this->once())
                ->method('insert')
                ->with($this->equalTo($data));

        $saver = new Xhgui_Saver_Mongo($profiles);
        $saver->save($data);


    }
} 
