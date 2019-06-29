<?php

use Slim\Http\Response;
use Slim\Slim;

class Controller_ImportTest extends CommonTestCase
{
    /**
     * @var PHPUnit_Framework_MockObject_MockObject|Xhgui_StorageInterface
     */
    protected $dbMock;
    
    /**
     * @var Xhgui_Controller_Import
     */
    protected $object;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|Response
     */
    protected $responseMock;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|Slim
     */
    protected $appMock;
    
    /**
     * Common setup
     */
    public function setUp()
    {
        parent::setUp();
        $di = Xhgui_ServiceContainer::instance();

        $this->appMock = $this->m(Slim::class, ['redirect', 'render', 'urlFor', 'request', 'response', 'flash', 'flashData']);

        $this->appMock->expects(self::any())->method('request')->willReturn($this->requestMock);
        $this->appMock->expects(self::any())->method('response')->willReturn($this->responseMock);

        $di['db'] = $di->share(function ($c) {
            return $this->dbMock;
        });

        $di['app'] = $di->share(function ($c) {
            return $this->appMock;
        });

        $this->object = $di['importController'];
        $this->app = $di['app'];

        $this->app->container = $this->m(\Slim\Helper\Set::class, ['get']);
    }

    /**
     * Test index action and make sure all handlers are present in UI
     */
    public function testIndex()
    {
        $this->app->container->expects(self::once())->method('get')->willReturn([
            'save.handler.filename'     => true,
            'save.handler.upload.uri'   => true,
            'db.host'                   => 'mongodb',
            'db.dsn'                    => true,
        ]);
        $this->object->index();
        $result = $this->object->templateVars();
        self::assertContains('file',    $result['configured_handlers']);
        self::assertContains('upload',  $result['configured_handlers']);
        self::assertContains('mongodb', $result['configured_handlers']);
        self::assertContains('pdo',     $result['configured_handlers']);
    }

    /**
     * Test import action
     */
    public function testImport()
    {
        $saverMock = $this->m(Xhgui_Saver::class, ['create']);
        $storageFactoryMock = $this->m(Xhgui_Storage_Factory::class, ['create']);

        $this->object->setSaver($saverMock);
        $this->object->setStorageFactory($storageFactoryMock);

        $this->app->container->expects(self::exactly(2))->method('get')->willReturn([
            'save.handler.filename'     => true,
            'save.handler.upload.uri'   => true,
            'db.host'                   => 'mongodb',
            'db.dsn'                    => true,
        ]);

        $this->preparePostRequestMock([
            ['source', null, 'file'],
            ['target', null, 'pdo'],
        ]);

        $reader = $this->m(Xhgui_Storage_File::class,   ['find']);
        $saver  = $this->m(Xhgui_Saver_File::class,     ['save']);

        $storageFactoryMock->expects(self::once())->method('create')->willReturn($reader);
        $saverMock->expects(self::once())->method('create')->willReturn($saver);

        $reader->expects(self::exactly(2))->method('find')->willReturnOnConsecutiveCalls(
            [
                ['row1'],
                ['row2'],
            ],
            []
        );

        $saver->expects(self::exactly(2))->method('save');

        $this->object->import();
    }
}
