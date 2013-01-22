<?php
class Xhgui_Twig_ExtensionTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        parent::setUp();
        autoloadTwig();
        $this->ext = new Xhgui_Twig_Extension();
    }

    public function testFormatBytes()
    {
        $result = $this->ext->formatBytes(2999);
        $expected = '2,999 <span class="units">bytes</span>';
        $this->assertEquals($expected, $result);
    }

    public function testFormatTime()
    {
        $result = $this->ext->formatTime(2999);
        $expected = '2,999 <span class="units">µs</span>';
        $this->assertEquals($expected, $result);
    }

    public static function urlProvider()
    {
        return array(
            // simple no query string
            array(
                '/index.php',
                null,
                '/xhgui/index.php'
            ),
            // simple with query string
            array(
                '/index.php',
                array('test' => 'value'),
                '/xhgui/index.php?test=value'
            ),
            // url already has ? on it.
            array(
                '/index.php?foo=bar',
                array('test' => 'value'),
                '/xhgui/index.php?foo=bar&test=value'
            ),

        );
    }

    /**
     * @dataProvider urlProvider
     */
    public function testUrl($url, $query, $expected)
    {
        $_SERVER['PHP_SELF'] = '/xhgui/index.php';
        $result = $this->ext->url($url, $query);
        $this->assertEquals($expected, $result);
    }

}
