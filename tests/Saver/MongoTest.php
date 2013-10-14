<?php

class Saver_MongoTest extends PHPunit_Framework_TestCase
{
    public function testSave()
    {
        $data = file_get_contents('tests/fixtures/results.json');

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
