<?php

class Saver_MongoTest extends PHPUnit\Framework\TestCase
{
    public function testSave()
    {
        $data = json_decode(file_get_contents(XHGUI_ROOT_DIR . '/tests/fixtures/results.json'), true);

        $collection = $this->getMockBuilder(MongoCollection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $collection->expects($this->once())
            ->method('insert')
            ->with($this->equalTo($data + ['_id' => Xhgui_Saver_Mongo::getLastProfilingId()]));

        $saver = new Xhgui_Saver_Mongo($collection);
        $saver->save($data);
    }
}
