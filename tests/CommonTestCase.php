<?php


use PHPUnit\Framework\TestCase;
use Slim\Http\Request;

class CommonTestCase extends PHPUnit\Framework\TestCase {
    /**
     * @var PHPUnit_Framework_MockObject_MockObject|\Slim\Http\Request
     */
    protected $requestMock;

    public function setUp(){
        $this->requestMock = $this->getMockBuilder(Request::class)
                                  ->setMethods(['get', 'post'])
                                  ->disableOriginalConstructor()
                                  ->getMock();
    }


    /**
     * @param array $override
     */
    protected function prepareGetRequestMock($override = []) {

        $default = [
            ['url', null, 'testUrl'],
            ['startDate', null, '2019-01-01'],
            ['endDate', null, '2019-02-01'],
            ['sort', null, 'time'],
            ['direction', null, 'desc'],
            ['page', null, '1'],
        ];

        $this->requestMock->expects(PHPUnit_Framework_TestCase::any())
                          ->method('get')
                          ->willReturnMap(array_merge($default, $override));
    }

    /**
     * @param array $override
     */
    protected function preparePostRequestMock($override = []) {

        $this->requestMock->expects(PHPUnit_Framework_TestCase::any())
                          ->method('post')
                          ->willReturnMap($override);
    }
}