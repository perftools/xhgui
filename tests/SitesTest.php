<?php

/**
 * Class Xhgui_SitesTest
 */
class Xhgui_SitesTest extends PHPUnit_Framework_TestCase
{
    /** @var Xhgui_Sites */
    private $object;

    /**
     */
    protected function setUp()
    {
        parent::setUp();
        $this->object = new Xhgui_Sites($this->mockDatabase());
    }

    /**
     * @covers Xhgui_Sites::setValidate
     * @covers Xhgui_Sites::isValidate
     */
    public function testCanSetAndGetValidate()
    {
        $this->object->setValidate(false);

        $this->assertFalse($this->object->isValidate());
    }

    /**
     * @covers Xhgui_Sites::isValidate
     */
    public function testValidateIsTrueByDefault()
    {
        $this->assertTrue($this->object->isValidate());
    }

    /**
     * @covers Xhgui_Sites::getAvailable
     */
    public function testCanFetchCurrentlyAvailableSiteCollections()
    {
        $db = $this->mockDatabaseWithCollections(
            array(
                'profiles_example.com',
                'profiles_example.org',
            )
        );
        $object = new Xhgui_Sites($db);

        $this->assertEquals(array('example.com', 'example.org'), $object->getAvailable());
    }

    /**
     * @covers Xhgui_Sites::getFirstAvailable
     */
    public function testCanFetchFirstCurrentlyAvailableSite()
    {
        $db = $this->mockDatabaseWithCollections(
            array(
                'profiles_example.com',
                'profiles_example.org',
            )
        );
        $object = new Xhgui_Sites($db);

        $this->assertEquals('example.com', $object->getFirstAvailable());
    }

    /**
     * @covers Xhgui_Sites::setCurrent
     * @covers Xhgui_Sites::getCurrent
     */
    public function testCanSetAndGetCurrentSite()
    {
        $db = $this->mockDatabaseWithCollections(
            array(
                'profiles_example.com',
                'profiles_example.org',
            )
        );
        $object = new Xhgui_Sites($db);
        $object->setCurrent('example.com');

        $this->assertEquals('example.com', $object->getCurrent());
    }

    /**
     * @covers Xhgui_Sites::hasCurrent
     */
    public function testCanTestIfHasCurrentSite()
    {
        $db = $this->mockDatabaseWithCollections(
            array(
                'profiles_example.com',
                'profiles_example.org',
            )
        );
        $object = new Xhgui_Sites($db);
        $object->setCurrent('example.com');

        $this->assertTrue($object->hasCurrent());
    }

    /**
     * @covers Xhgui_Sites::setCurrent
     */
    public function testSettingAnInvalidCurrentSiteThrowsAnInvalidArgumentException()
    {
        $this->setExpectedException('InvalidArgumentException', 'No such site');
        $db = $this->mockDatabaseWithCollections(
            array(
                'profiles_example.com',
                'profiles_example.org',
            )
        );
        $object = new Xhgui_Sites($db);

        $object->setCurrent('no-such.site');
    }

    /**
     * @covers Xhgui_Sites::setCurrent
     */
    public function testCanSetInvalidCurrentIfValidateIsDisabled()
    {
        $this->object->setValidate(false);
        $this->object->setCurrent('no-such.site');
    }

    /**
     * @covers Xhgui_Sites::getCurrentCollection
     * @covers Xhgui_Sites::<protected>
     */
    public function testCanGetCurrentCollection()
    {
        $this->object->setValidate(false);
        $this->object->setCurrent('no-such.site');

        $this->assertEquals('profiles_no-such.site', $this->object->getCurrentCollection());
    }

    /**
     * @param array $methods
     *
     * @return MongoDb|PHPUnit_Framework_MockObject_MockObject
     */
    private function mockDatabase(array $methods = null)
    {
        $db = $this->getMockBuilder('MongoDb')
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMock();

        return $db;
    }

    /**
     * @param array $names
     *
     * @return MongoDb|PHPUnit_Framework_MockObject_MockObject
     */
    private function mockDatabaseWithCollections(array $names)
    {
        $collections = array();
        foreach ($names as $name) {
            $collections[] = $this->mockCollection($name);
        }

        $db = $this->mockDatabase(array('listCollections'));
        $db
            ->expects($this->once())
            ->method('listCollections')
            ->will($this->returnValue($collections));

        return $db;
    }

    /**
     * @param array $name
     *
     * @return MongoCollection|PHPUnit_Framework_MockObject_MockObject
     */
    private function mockCollection($name)
    {
        $collection = $this->getMockBuilder('MongoCollection')
            ->disableOriginalConstructor()
            ->setMethods(array('getName'))
            ->getMock();
        $collection
            ->expects($this->once())
            ->method('getName')
            ->will($this->returnValue($name));

        return $collection;
    }
}
