<?php


class MongoTest extends CommonTestCase
{

    /**
     * @var Xhgui_Storage_Mongo
     */
    public $object;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|\MongoCollection
     */
    public $collectionMock;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|\MongoDb
     */
    public $connectionMock;

    /**
     * @throws MongoConnectionException
     * @throws MongoException
     */
    public function setUp()
    {
        $this->object = new Xhgui_Storage_Mongo([
            'page.limit' => 20,
            'db.options' => [],
            'db.db' => 'results',
        ]);

        $this->collectionMock = $this->m(ArrayIterator::class, [
            'find',
            'sort',
            'skip',
            'limit',
            'count',
            'findOne',
            'remove',
            'insert',
            'update'
        ]);
        $this->connectionMock = $this->m(MongoDB::class, ['selectCollection']);
        $this->connectionMock->expects(self::any())->method('selectCollection')->willReturn($this->collectionMock);

        $this->object->setConnection($this->connectionMock);
    }

    /**
     * @throws MongoCursorException
     */
    public function testFind()
    {

        $filter = new Xhgui_Storage_Filter();
        $filter->setSort('ct');
        $filter->setDirection('asc');

        $this->collectionMock->expects(self::once())->method('find')->willReturnSelf();
        $this->collectionMock->expects(self::once())->method('sort')->willReturnSelf();
        $this->collectionMock->expects(self::once())->method('skip')->willReturnSelf();
        $this->collectionMock->expects(self::once())->method('limit')->willReturnSelf();

        $ret = $this->object->find($filter);
        self::assertInstanceOf(Xhgui_Storage_ResultSet::class, $ret);
    }

    public function testCount()
    {
        $filter = new Xhgui_Storage_Filter();
        $filter->setSort('ct');
        $filter->setDirection('asc');

        $this->collectionMock->expects(self::once())->method('find')->willReturnSelf();
        $this->collectionMock->expects(self::once())->method('count')->willReturn(5);

        $ret = $this->object->count($filter);
        self::assertSame(5, $ret);
    }

    public function testFindOne()
    {
        $this->collectionMock->expects(self::once())->method('findOne')->willReturn(5);

        $ret = $this->object->findOne(1);
        self::assertSame(5, $ret);
    }


    /**
     * 
     */
    public function testRemove()
    {
        $this->collectionMock->expects(self::once())->method('remove')->willReturn(true);
        
        $ret = $this->object->remove(1);
        self::assertTrue($ret);
    }

    /**
     *
     */
    public function crudDataProvider()
    {
        return [
            [true, 'remove', true, [1]],
        ];
    }

//    public function testGetWatchedFunctions() {
//
//    }
//
//    public function testUpdateWatchedFunction() {
//
//    }
//
//    public function testAddWatchedFunction() {
//
//    }
//
//    public function testRemoveWatchedFunction() {
//
//    }
}
