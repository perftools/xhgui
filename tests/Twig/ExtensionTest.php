<?php
use Slim\Slim;
use Slim\Environment;

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

    /**
     * @return array
     */
    public function makePercentProvider() {
        return array(
            array(
                10,
                100,
                '10 <span class="units">%</span>'
            ),
            array(
                0.5,
                100,
                '1 <span class="units">%</span>'
            ),
            array(
                100,
                0,
                '0 <span class="units">%</span>'
            )
        );
    }

    /**
     * @dataProvider makePercentProvider
     */
    public function testMakePercent($value, $total, $expected)
    {
        $result = $this->ext->makePercent($value, $total, $total);
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

    public function testStaticUrlNoIndexPhp()
    {
        Environment::mock(array(
            'SCRIPT_NAME' => '/index.php',
            'PHP_SELF' => '/index.php',
            'REQUEST_URI' => '/',
        ));
        $result = $this->ext->staticUrl('css/bootstrap.css');
        $this->assertEquals('/css/bootstrap.css', $result);
    }

    public function testStaticUrlWithIndexPhp()
    {
        Environment::mock(array(
            'SCRIPT_NAME' => '/xhgui/webroot/index.php',
            'PHP_SELF' => '/xhgui/webroot/index.php/',
            'REQUEST_URI' => '/xhgui/webroot/index.php/',
        ));
        $result = $this->ext->staticUrl('css/bootstrap.css');
        $this->assertEquals('/xhgui/webroot/css/bootstrap.css', $result);
    }
}
