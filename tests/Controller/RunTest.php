<?php

use Slim\Http\Response;
use Slim\Slim;

class Controller_RunTest extends CommonTestCase
{
    /**
     * @var PHPUnit_Framework_MockObject_MockObject|Xhgui_StorageInterface
     */
    protected $dbMock;

    /**
     * @var Xhgui_Controller_Run
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
            'get',
            'getPercentileForUrl',
            'getRelatives',
            'delete',
            'truncate'
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

        $this->object = $di['runController'];
        $this->app = $di['app'];

        $this->object->setWatches($this->dbMock);
    }

    /**
     */
    public function testIndexEmpty()
    {
        $sort = 'time';
        $this->profilesMock->expects(self::once())->method('getAll')->willReturn([
            'totalPages' => 1,
            'page' => 1,
            'direction' => $sort,
            'results' => [],
            'has_search' => 'No search being done.'
        ]);

        $this->prepareGetRequestMock(['sort', 'time']);

        $this->object->index();
        $result = $this->object->templateVars();

        $this->assertEquals('Recent runs', $result['title']);
        $expected = array(
            'total_pages' => 1,
            'sort' => 'time',
            'page' => 1,
            'direction' => $sort,
        );
        $this->assertEquals($expected, $result['paging']);
    }

    /**
     *
     */
    public function testIndexWithSearch()
    {
        $this->prepareGetRequestMock(['sort', 'time']);

        $this->object->index();
        $result = $this->object->templateVars();
        $this->assertEquals('Recent runs', $result['title']);
        $this->assertEquals('time', $result['paging']['sort']);
        $this->assertArrayHasKey('url', $result['search']);
        $this->assertEquals('testUrl', $result['search']['url']);
    }

    /**
     *
     */
    public function testView()
    {
        $id = 1;
        $this->prepareGetRequestMock([
            ['id', null, $id],
        ]);

        // mocked result set.
        $profileMock = $this->m(Xhgui_Profile::class, [
            'calculateSelf',
            'extractDimension',
            'getWatched',
            'getProfile',
            'sort'
        ]);

        $profileMock->expects(self::any())->method('calculateSelf');
        $profileMock->expects(self::exactly(2))->method('extractDimension')->willReturnOnConsecutiveCalls(
            'ewt_values',
            'emu_values'
        );

        // watched functions
        $profileMock->expects(self::any())->method('sort')->willReturnSelf();

        $this->dbMock->expects(self::any())->method('getWatchedFunctions')->willReturn([
            ['name' => 'testWatchedFunction']
        ]);

        // mergein matched function 
        $profileMock->expects(self::any())
                    ->method('getWatched')
                    ->with($this->stringContains('testWatchedFunction'))
                    ->willReturn(['test']);

        $this->profilesMock->expects(self::any())->method('get')->willReturn($profileMock);

        // run controller action
        $this->object->view();
        // get results passed to template
        $result = $this->object->templateVars();

        self::assertSame($profileMock, $result['profile']);
        self::assertSame($profileMock, $result['result']);
        self::assertSame('ewt_values', $result['wall_time']);
        self::assertSame('emu_values', $result['memory']);
    }


    public function testUrl()
    {
        $url = 'testUrl';
        $this->prepareGetRequestMock([
            ['url', $url],
        ]);

        // make sure that we call profiles storage with url filter
        $this->profilesMock->expects(self::once())
                           ->method('getAll')
                           ->with($this->callback(function ($filter) use ($url) {
                               if (!($filter instanceof Xhgui_Storage_Filter)) {
                                   return false;
                               }

                               return $filter->getUrl() == $url;
                           }))->willReturn([
                'results' => ['fake_results'],
                'totalPages' => 0,
                'page' => 0,
                'direction' => 'desc',
            ]);

        // chart data. For this we mock it with fake data, we don't process it action
        // we just pass it to view
        $this->profilesMock->expects(self::once())
                           ->method('getPercentileForUrl')
                           ->willReturn('mocked_chart_data');

        $this->object->url();

        $result = $this->object->templateVars();

        self::assertSame($url, $result['url']);
        self::assertSame(['fake_results'], $result['runs']);
        self::assertSame(['fake_results'], $result['runs']);
    }

    public function testCompareNoBase()
    {
        $base = null;
        $head = null;
        $this->prepareGetRequestMock([
            ['base', null, $base],
            ['head', null, $head],
        ]);

        $this->object->compare();
        $result = $this->object->templateVars();

        self::assertNull($result['base_run']);
        self::assertNull($result['head_run']);
        self::assertSame($base, $result['search']['base']);
        self::assertSame($head, $result['search']['head']);

        self::assertNull($result['candidates']);
        self::assertNull($result['comparison']);
    }

    public function testCompareWithBase()
    {
        $base = 1;
        $head = null;
        $url = 'testUrl';
        $this->prepareGetRequestMock([
            ['base', null, $base],
            ['head', null, $head]
        ]);

        // mocked result set.
        $baseRunMock = $this->m(Xhgui_Profile::class, [
            'getMeta',
            'compare',
        ]);

        $baseRunMock->expects(self::any())->method('getMeta')->willReturnMap([
            ['simple_url', $url]
        ]);
        $this->profilesMock->expects(self::once())->method('get')->willReturn($baseRunMock);

        // get candidate
        $this->profilesMock->expects(self::once())
                           ->method('getAll')
                           ->with($this->callback(function ($filter) use ($url) {
                               if (!($filter instanceof Xhgui_Storage_Filter)) {
                                   return false;
                               }

                               return $filter->getUrl() == $url;
                           }))
                           ->willReturn([
                               'results' => ['fake_results'],
                               'totalPages' => 0,
                               'page' => 0,
                               'direction' => 'desc',
                           ]);

        $this->object->compare();
        $result = $this->object->templateVars();

        self::assertInstanceOf(Xhgui_Profile::class, $result['base_run']);
        self::assertNull($result['head_run']);
        self::assertSame($base, $result['search']['base']);
        self::assertSame($head, $result['search']['head']);

        self::assertNotNull($result['candidates']['results']);
        self::assertNull($result['comparison']);
    }

    public function testCompareWithBaseAndHead()
    {
        $base = 1;
        $head = 2;
        $url = 'testUrl';
        $compareResult = "CompareResult";

        $this->prepareGetRequestMock([
            ['base', null, $base],
            ['head', null, $head]
        ]);

        // mocked result set.
        $baseRunMock = $this->m(Xhgui_Profile::class, [
            'getMeta',
            'compare',
        ]);

        $baseRunMock->expects(self::any())->method('getMeta')->willReturnMap([
            ['simple_url', $url]
        ]);
        $baseRunMock->expects(self::once())->method('compare')->willReturn($compareResult);

        // mocked result set.
        $headRunMock = $this->m(Xhgui_Profile::class);

        $this->profilesMock->expects(self::exactly(2))->method('get')->willReturnMap([
            [$base, $baseRunMock],
            [$head, $headRunMock]
        ]);

        $this->object->compare();
        $result = $this->object->templateVars();

        self::assertInstanceOf(Xhgui_Profile::class, $result['base_run']);
        self::assertInstanceOf(Xhgui_Profile::class, $result['head_run']);
        self::assertSame($base, $result['search']['base']);
        self::assertSame($head, $result['search']['head']);

        self::assertNull($result['candidates']['results']);
        self::assertSame($compareResult, $result['comparison']);
    }

    public function testSymbol()
    {
        $id = 1;
        $symbol = 'main()';

        $this->prepareGetRequestMock([
            ['id', null, $id],
            ['symbol', null, $symbol]
        ]);
        // mocked result set.
        $profileMock = $this->m(Xhgui_Profile::class, [
            'calculateSelf',
            'getRelatives',
        ]);

        $profileMock->expects(self::any())
                    ->method('calculateSelf');


        $profileMock->expects(self::any())
                    ->method('getRelatives')
                    ->with($this->equalTo($symbol))
                    ->willReturn(['parents', 'current', 'children']);

        $this->profilesMock->expects(self::any())
                           ->method('get')
                           ->with($this->equalTo($id))
                           ->willReturn($profileMock);

        $this->object->symbol();
        $result = $this->object->templateVars();

        self::assertSame('parents', $result['parents']);
        self::assertSame('current', $result['current']);
        self::assertSame('children', $result['children']);

    }

    public function testSymbolShort()
    {
        $id = 1;
        $threshold = 2;
        $symbol = 'main()';
        $metric = 3;

        $this->prepareGetRequestMock([
            ['id', null, $id],
            ['threshold', null, $threshold],
            ['symbol', null, $symbol],
            ['metric', null, $metric],
        ]);
        // mocked result set.
        $profileMock = $this->m(Xhgui_Profile::class, [
            'calculateSelf',
            'getRelatives',
        ]);

        $profileMock->expects(self::any())
                    ->method('calculateSelf');


        $profileMock->expects(self::any())
                    ->method('getRelatives')
                    ->with($this->equalTo($symbol), $this->equalTo($metric), $this->equalTo($threshold))
                    ->willReturn(['parents', 'current', 'children']);

        $this->profilesMock->expects(self::any())
                           ->method('get')
                           ->with($this->equalTo($id))
                           ->willReturn($profileMock);

        $this->object->symbolShort();
        $result = $this->object->templateVars();

        self::assertSame('parents', $result['parents']);
        self::assertSame('current', $result['current']);
        self::assertSame('children', $result['children']);

    }

    public function testCallgraph()
    {
        $id = 1;

        $this->prepareGetRequestMock([
            ['id', null, $id],
        ]);

        // mocked result set.
        $profileMock = $this->m(Xhgui_Profile::class);

        $this->profilesMock->expects(self::any())
                           ->method('get')
                           ->with($this->equalTo($id))
                           ->willReturn($profileMock);

        $this->object->callgraph();
        $result = $this->object->templateVars();

        $this->assertArrayHasKey('profile', $result);
        $this->assertArrayHasKey('date_format', $result);
        $this->assertArrayNotHasKey('callgraph', $result);
    }

    public function testCallgraphData()
    {
        $id = 1;
        $metric = 'metric';
        $threshold = 1;

        $this->prepareGetRequestMock([
            ['id', null, $id],
            ['metric', null, $metric],
            ['threshold', null, $threshold],
        ]);

        // mocked result set.
        $profileMock = $this->m(Xhgui_Profile::class, [
            'getCallgraphNodes',
            'getCallgraph'
        ]);

        $this->profilesMock->expects(self::any())
                           ->method('get')
                           ->with($this->equalTo($id))
                           ->willReturn($profileMock);

        $responseMock = $this->m(Response::class, ['body']);
        $responseMock->expects(self::exactly(2))->method('body')->willReturnOnConsecutiveCalls(
            [''],
            '{"'
        );

        $this->appMock->expects(self::exactly(2))->method('response')->willReturn($responseMock);

        $this->object->callgraphData();
        $response = $this->app->response();

        $this->assertEquals('application/json', $response['Content-Type']);
        $this->assertStringStartsWith('{"', $response->body());
    }

    /**
     * @throws Exception
     */
    public function testDeleteForm()
    {
        $id = 1;

        $this->prepareGetRequestMock([
            ['id', null, $id],
        ]);

        // mocked result set.
        $profileMock = $this->m(Xhgui_Profile::class);

        $this->profilesMock->expects(self::any())
                           ->method('get')
                           ->with($this->equalTo($id))
                           ->willReturn($profileMock);

        $this->object->deleteForm();
        $result = $this->object->templateVars();

        self::assertArrayHasKey('result', $result);
        self::assertSame($profileMock, $result['result']);

    }

    /**
     *
     */
    public function testDeleteSubmit()
    {
        $id = 1;

        $this->preparePostRequestMock([
            ['id', null, $id],
        ]);

        // mocked result set.
        $profileMock = $this->m(Xhgui_Profile::class);

        $this->profilesMock->expects(self::any())
                           ->method('delete')
                           ->with($this->equalTo($id))
                           ->willReturn($profileMock);

        $this->appMock->expects(self::once())->method('urlFor');
        $this->appMock->expects(self::once())->method('redirect');

        $this->object->deleteSubmit();
    }

    public function testDeleteAllSubmit()
    {
        $this->profilesMock->expects(self::any())
                           ->method('truncate');

        $this->appMock->expects(self::once())->method('urlFor');
        $this->appMock->expects(self::once())->method('redirect');

        $this->object->deleteAllSubmit();

    }
}
