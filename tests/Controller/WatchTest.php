<?php
use Slim\Environment;

class Controller_WatchTest extends PHPUnit\Framework\TestCase
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

        $mock = $this->getMockBuilder('Slim\Slim')
            ->setMethods(array('redirect', 'render', 'urlFor'))
            ->setConstructorArgs(array($di['config']))
            ->getMock();
        $di['app'] = $di->share(function ($c) use ($mock) {
            return $mock;
        });
        $this->watches = $di['watchController'];
        $this->app = $di['app'];
        $this->searcher = $di['searcher'];
        $this->searcher->truncateWatches();
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
        $result = $this->searcher->getAllWatches();

        $this->assertCount(2, $result);
        $this->assertEquals('strlen', $result[0]['name']);
        $this->assertEquals('strpos', $result[1]['name']);
    }

    public function testPostModify()
    {
        $this->searcher->saveWatch(array('name' => 'strlen'));
        $saved = $this->searcher->getAllWatches();

        $_POST = array(
            'watch' => array(
                array('name' => 'strpos', '_id' => $saved[0]['_id'])
            )
        );
        $this->watches->post();
        $result = $this->searcher->getAllWatches();

        $this->assertCount(1, $result);
        $this->assertEquals('strpos', $result[0]['name']);
    }

    public function testPostDelete()
    {
        $this->searcher->saveWatch(array('name' => 'strlen'));
        $saved = $this->searcher->getAllWatches();

        $_POST = array(
            'watch' => array(
                array('removed' => 1, 'name' => 'strpos', '_id' => $saved[0]['_id'])
            )
        );
        $this->watches->post();
        $result = $this->searcher->getAllWatches();

        $this->assertCount(0, $result);
    }

}
