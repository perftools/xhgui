<?php

namespace XHGui\Test;

use XHGui\Config;

class ConfigTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        Config::clear();
    }

    public function testReadWrite()
    {
        $result = Config::write('test', 'value');
        $this->assertNull($result);

        $result = Config::read('test');
        $this->assertEquals('value', $result);

        $result = Config::read('not there');
        $this->assertNull($result);
    }

    public function testReadWriteWithDots()
    {
        $result = Config::write('test.name', 'value');
        $this->assertNull(Config::read('test'));
        $this->assertEquals('value', Config::read('test.name'));
    }

    public function testClear()
    {
        Config::write('test', 'value');
        $this->assertNull(Config::clear());
        $this->assertNull(Config::read('test'));
    }
}
