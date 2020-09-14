<?php

namespace XHGui\Test\Saver;

use MongoCollection;
use XHGui\Test\TestCase;
use Xhgui_Saver_Mongo;

class MongoTest extends TestCase
{
    public function testSave()
    {
        $data = json_decode(file_get_contents(XHGUI_ROOT_DIR . '/tests/fixtures/results.json'), true);

        $collection = $this->getMockBuilder(MongoCollection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $collection->expects($this->exactly(5))
            ->method('insert')
            ->withConsecutive($this->equalTo($data));

        $saver = new Xhgui_Saver_Mongo($collection);

        foreach ($data as $profile) {
            $saver->save($profile);
        }
    }
}
