<?php


use PHPUnit\Framework\TestCase;
use Slim\Http\Headers;
use Slim\Http\Request;
use Slim\Http\Response;

class CommonTestCase extends PHPUnit\Framework\TestCase
{
    /**
     * @var PHPUnit_Framework_MockObject_MockObject|\Slim\Http\Request
     */
    protected $requestMock;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|\Slim\Http\Response
     */
    protected $responseMock;

    /**
     * Basic setup
     */
    public function setUp()
    {
        $this->requestMock = $this->getMockBuilder(Request::class)
                                  ->setMethods(['get', 'post'])
                                  ->disableOriginalConstructor()
                                  ->getMock();

        $this->responseMock = $this->getMockBuilder(Response::class)
                                  ->setMethods(['get', 'post', 'body'])
                                  ->disableOriginalConstructor()
                                  ->getMock();

        $this->responseMock->headers = $this->getMockBuilder(Headers::class)
                                  ->setMethods()
                                  ->disableOriginalConstructor()
                                  ->getMock();
    }


    /**
     * @param array $override
     */
    protected function prepareGetRequestMock($override = [])
    {

        $default = [
            ['url',         null, 'testUrl'],
            ['startDate',   null, '2019-01-01'],
            ['endDate',     null, '2019-02-01'],
            ['sort',        null, 'time'],
            ['direction',   null, 'desc'],
            ['page',        null, '1'],
        ];

        $this->requestMock->expects(self::any())
                          ->method('get')
                          ->willReturnMap(array_merge($default, $override));
    }

    /**
     * @param array $override
     */
    protected function preparePostRequestMock($override = [])
    {

        $this->requestMock->expects(self::any())
                          ->method('post')
                          ->willReturnMap($override);
    }

    /**
     * Shorthand helper for mock creation
     *
     * @param $class
     * @param array $methods
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    protected function m($class, $methods = [])
    {
        return $this->getMockBuilder($class)->disableOriginalConstructor()->setMethods($methods)->getMock();
    }
}
