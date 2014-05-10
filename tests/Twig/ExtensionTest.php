<?php
use Slim\Slim;

class Xhgui_Twig_ExtensionTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        parent::setUp();
        $app = new Slim();
        $app->get('/test', function () {})->name('test');
        $this->ext = new Xhgui_Twig_Extension($app);
    }

    public function testFormatBytes()
    {
        $result = $this->ext->formatBytes(2999);
        $expected = '2,999&nbsp;<span class="units">bytes</span>';
        $this->assertEquals($expected, $result);
    }

    public function testFormatTime()
    {
        $result = $this->ext->formatTime(2999);
        $expected = '2,999&nbsp;<span class="units">µs</span>';
        $this->assertEquals($expected, $result);
    }

    public static function urlProvider()
    {
        return array(
            // simple no query string
            array(
                'test',
                null,
                '/test'
            ),
            // simple with query string
            array(
                'test',
                array('test' => 'value'),
                '/test?test=value'
            ),
        );
    }

    /**
     * @dataProvider urlProvider
     */
    public function testUrl($url, $query, $expected)
    {
        $_SERVER['PHP_SELF'] = '/';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['SERVER_PORT'] = '80';

        $result = $this->ext->url($url, $query);
        $this->assertStringEndsWith($expected, $result);
    }

}
