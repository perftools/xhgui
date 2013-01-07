<?php

class ConfigTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        parent::setUp();
        Xhgui_Config::clear();
    }

    public function testReadWrite()
    {
        $result = Xhgui_Config::write('test', 'value');
        $this->assertNull($result);

        $result = Xhgui_Config::read('test');
        $this->assertEquals('value', $result);

        $result = Xhgui_Config::read('not there');
        $this->assertNull($result);
    }

    public function testReadWriteWithDots()
    {
        $result = Xhgui_Config::write('test.name', 'value');
        $this->assertNull(Xhgui_Config::read('test'));
        $this->assertEquals('value', Xhgui_Config::read('test.name'));
    }

    public function testClear()
    {
        Xhgui_Config::write('test', 'value');
        $this->assertNull(Xhgui_Config::clear());
        $this->assertNull(Xhgui_Config::read('test'));
    }

}
