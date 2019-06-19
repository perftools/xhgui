<?php
use Slim\Environment;
use Slim\Slim;

class Controller_WatchTest extends CommonTestCase
{
    /**
     * @var PHPUnit_Framework_MockObject_MockObject|Xhgui_StorageInterface
     */
    protected $dbMock;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|Slim
     */
    protected $appMock;
    
    /**
     * Setup method
     */
    public function setUp()
    {
        parent::setUp();
        $di = Xhgui_ServiceContainer::instance();

        $this->appMock  = $this->m(Slim::class, ['redirect', 'render', 'urlFor', 'request', 'response', 'flash']);

        $this->dbMock   = $this->m(Xhgui_Storage_File::class, [
            'getAll',
            'getWatchedFunctions',
            'addWatchedFunction',
            'removeWatchedFunction',
            'updateWatchedFunction'
        ]);

        $this->appMock->expects(self::any())->method('request')->willReturn($this->requestMock);

        $di['app'] = $di->share(function ($c) {
            return $this->appMock;
        });

        $di['db'] = $di->share(function ($c) {
            return $this->dbMock;
        });

        $this->watches = $di['watchController'];
        $this->app = $di['app'];
    }

    public function testGet()
    {
        $expected = 'getWatchedFunctions return';
        $this->dbMock->expects(self::once())->method('getWatchedFunctions')->willReturn($expected);

        $this->watches->get();
        $result = $this->watches->templateVars();
        $this->assertEquals($expected, $result['watched']);
    }


    /**
     * @param string $method
     * @param array  $payload
     * @dataProvider postDataProvider
     */
    public function testPost($method, $payload)
    {
        $this->preparePostRequestMock([
            ['watch', null, $payload],
        ]);

        $this->dbMock->expects(self::exactly(2))->method($method);

        $this->appMock->expects(self::once())->method('urlFor');
        $this->appMock->expects(self::once())->method('redirect');

        $this->watches->post();
    }

    public function postDataProvider()
    {
        return [
            ['updateWatchedFunction', [['id'=>1, 'name'=>'test'], ['id'=>2, 'name'=>'different test']]],
            ['addWatchedFunction', [['name'=>'test'], ['name'=>'different test']]],
            ['removeWatchedFunction', [['id'=>1, 'removed'=>'1'], ['id'=>2, 'removed'=>'1']]],
        ];
    }
}
