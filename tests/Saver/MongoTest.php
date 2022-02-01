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

        $collection
            ->expects($this->exactly(count($data)))
            ->method('insert')
            ->withConsecutive(...array_map(function () {
                return [
                    $this->callback(function ($data) {
                        $this->assertIsArray($data);
                        $this->assertArrayHasKey('_id', $data);
                        $this->assertArrayHasKey('meta', $data);
                        $this->assertArrayHasKey('profile', $data);

                        return true;
                    }),
                    $this->equalTo(['w' => 0]),
                ];
            }, $data));

        $saver = new MongoSaver($collection);

        foreach ($data as $profile) {
            $saver->save($profile, $profile['_id'] ?? null);
        }
    }
}
