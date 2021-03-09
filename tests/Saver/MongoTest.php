<?php

namespace XHGui\Test\Saver;

use MongoCollection;
use XHGui\Saver\MongoSaver;
use XHGui\Test\TestCase;

class MongoTest extends TestCase
{
    public function testSave(): void
    {
        $this->skipIfPdo('This is MongoDB test');

        $data = $this->loadFixture('normalized.json');

        $collection = $this->getMockBuilder(MongoCollection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $collection->expects($this->exactly(count($data)))
            ->method('insert')
            ->withConsecutive($this->equalTo($data));

        $saver = new MongoSaver($collection);

        foreach ($data as $profile) {
            $saver->save($profile, $profile['_id'] ?? null);
        }
    }
}
