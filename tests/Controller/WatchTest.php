<?php
use Slim\Environment;

class Controller_WatchTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        parent::setUp();
        Environment::mock(array(
           'SCRIPT_NAME' => 'index.php',
           'PATH_INFO' => '/watch'
        ));
        $di = Xhgui_ServiceContainer::instance();
        unset($di['app']);

        $mock = $this->getMock(
                'Slim\Slim',
                array('redirect', 'render', 'urlFor'),
                array($di['config'])
            );
        $di['app'] = $di->share(function ($c) use ($mock) {
            return $mock;
        });
        $this->watches = $di['watchController'];
        $this->app = $di['app'];
        $this->watchFunctions = $di['watchFunctions'];
        $this->watchFunctions->truncate();
    }

    public function testGet()
    {
        $this->watches->get();
        $result = $this->watches->templateVars();
        $this->assertEquals(array(), $result['watched']);
    }

    public function testPostAdd()
    {
        $_POST = array(
            'watch' => array(
                array('name' => 'strlen'),
                array('name' => 'strpos')
            )
        );
        $this->app->expects($this->once())
            ->method('urlFor')
            ->with('watch.list');

        $this->app->expects($this->once())
            ->method('redirect');

        $this->watches->post();
        $result = $this->watchFunctions->getAll();

        $this->assertCount(2, $result);
        $this->assertEquals('strlen', $result[0]['name']);
        $this->assertEquals('strpos', $result[1]['name']);
    }

    public function testPostModify()
    {
        $this->watchFunctions->save(array('name' => 'strlen'));
        $saved = $this->watchFunctions->getAll();

        $_POST = array(
            'watch' => array(
                array('name' => 'strpos', '_id' => $saved[0]['_id'])
            )
        );
        $this->watches->post();
        $result = $this->watchFunctions->getAll();

        $this->assertCount(1, $result);
        $this->assertEquals('strpos', $result[0]['name']);
    }

    public function testPostDelete()
    {
        $this->watchFunctions->save(array('name' => 'strlen'));
        $saved = $this->watchFunctions->getAll();

        $_POST = array(
            'watch' => array(
                array('removed' => 1, 'name' => 'strpos', '_id' => $saved[0]['_id'])
            )
        );
        $this->watches->post();
        $result = $this->watchFunctions->getAll();

        $this->assertCount(0, $result);
    }

}
