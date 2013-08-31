<?php
use Slim\Environment;

class Controller_RunTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        parent::setUp();
        Environment::mock(array(
            'SCRIPT_NAME' => 'index.php',
            'PATH_INFO' => '/'
        ));
        $di = Xhgui_ServiceContainer::instance();
        unset($di['app']);

        $di['app'] = $di->share(function ($c) {
            return $this->getMock(
                'Slim\Slim',
                array('redirect', 'render', 'urlFor'),
                array($c['config'])
            );
        });
        $this->runs = $di['runController'];
        $this->app = $di['app'];
        $this->profiles = $di['profiles'];
        $this->profiles->truncate();
    }

    public function testIndexEmpty()
    {
        $this->runs->index();
        $result = $this->runs->templateVars();

        $this->assertEquals('Recent runs', $result['title']);
        $this->assertFalse($result['has_search'], 'No search being done.');
        $expected = array(
            'total_pages' => 1,
            'page' => 1,
            'sort' => null,
            'direction' => 'desc',
        );
        $this->assertEquals($expected, $result['paging']);
    }

}
