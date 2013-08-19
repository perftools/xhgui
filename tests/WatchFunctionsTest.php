<?php
class WatchFunctionsTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        parent::setUp();
        $di = Xhgui_ServiceContainer::instance();
        $this->watch = $di['watchFunctions'];
        $this->watch->truncate();
    }

    public function testSaveInsert()
    {
        $data = array(
            'name' => 'strlen',
        );
        $this->assertTrue($this->watch->save($data));
        $this->assertCount(1, $this->watch->getAll());

        $data = array(
            'name' => 'empty',
        );
        $this->assertTrue($this->watch->save($data));
        $this->assertCount(2, $this->watch->getAll());
    }

    public function testSaveUpdate()
    {
        $data = array(
            'name' => 'strlen',
        );
        $this->watch->save($data);
        $result = $this->watch->getAll();

        $result[0]['name'] = 'strpos';
        $this->assertTrue($this->watch->save($result[0]));
        $results = $this->watch->getAll();
        $this->assertCount(1, $results);
        $this->assertEquals('strpos', $results[0]['name']);
    }

    public function testSaveRemove()
    {
        $data = array(
            'name' => 'strlen',
        );
        $this->watch->save($data);
        $result = $this->watch->getAll();

        $result[0]['removed'] = 1;
        $this->assertTrue($this->watch->save($result[0]));
        $this->assertCount(0, $this->watch->getAll());
    }

}
