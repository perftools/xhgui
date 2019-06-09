<?php

use Slim\Http\Response;
use Slim\Slim;

class Controller_WaterfallTest extends CommonTestCase
{
    /**
     * @var PHPUnit_Framework_MockObject_MockObject|Xhgui_StorageInterface
     */
    protected $dbMock;

    /**
     * @var Xhgui_Controller_Waterfall
     */
    protected $object;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|Xhgui_Profiles
     */
    protected $profilesMock;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|Response
     */
    protected $responseMock;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|Slim
     */
    protected $appMock;

    /**
     * @return mixed|void
     */
    public function setUp()
    {
        parent::setUp();
        $di = Xhgui_ServiceContainer::instance();

        $this->profilesMock = $this->m(Xhgui_Profiles::class, [
            'getAll',
        ]);

        $this->appMock = $this->m(Slim::class, ['redirect', 'render', 'urlFor', 'request', 'response', 'flash']);

        $this->dbMock = $this->m(Xhgui_Storage_File::class, ['getAll', 'getWatchedFunctions']);

        $this->appMock->expects(self::any())->method('request')->willReturn($this->requestMock);

        $di['db'] = $di->share(function ($c) {
            return $this->dbMock;
        });

        $di['app'] = $di->share(function ($c) {
            return $this->appMock;
        });

        $di['profiles'] = $di->share(function ($c) {
            return $this->profilesMock;
        });

        $this->object = $di['waterfallController'];
        $this->app = $di['app'];
    }

    /**
     *
     */
    public function testIndex()
    {
        $this->profilesMock->expects(self::once())->method('getAll')->willReturn([
            'totalPages' => 1,
            'page' => 1,
            'direction' => 'asc',
            'results' => ['results'],
        ]);

        $this->object->index();
        $result = $this->object->templateVars();

        self::assertArrayHasKey('runs', $result);
        self::assertArrayHasKey('search', $result);
        self::assertArrayHasKey('paging', $result);
        self::assertArrayHasKey('base_url', $result);

        self::assertSame(['results'], $result['runs']);
    }

    /**
     *
     */
    public function testQuery()
    {
        $profile = $this->m(Xhgui_Profile::class, ['get', 'getMeta', 'getId']);

        $profile->expects(self::once())->method('get')->willReturn(10);
        $profile->expects(self::exactly(2))->method('getMeta')->willReturnOnConsecutiveCalls(1,'url');
        $profile->expects(self::once())->method('getId')->willReturn('id');

        $responseMock = $this->m(Response::class, ['body']);
        $this->appMock->expects(self::exactly(1))->method('response')->willReturn($responseMock);

        $responseMock->expects(self::once())->method('body')->with($this->callback(function ($resp) {
            return '[{"id":"id","title":"url","start":1000,"duration":0.01}]' === $resp;
        }));

        $this->profilesMock->expects(self::once())->method('getAll')->willReturn([
            'totalPages' => 1,
            'page' => 1,
            'direction' => 'asc',
            'results' => [
                $profile
            ],
        ]);

        $this->object->query();
    }
}
