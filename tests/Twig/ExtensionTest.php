<?php
use Slim\Slim;

class Xhgui_Twig_ExtensionTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        parent::setUp();
        $app = new Slim();
        $app->get('/test', function () {})->name('test');
        $sites = $this->getMockBuilder('Xhgui_Sites')
            ->disableOriginalConstructor()
            ->getMock();

        $this->ext = new Xhgui_Twig_Extension($app, $sites);
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

    public function testCanGetAvailableSites()
    {
        $app = $this->getMockBuilder('Slim\Slim')
            ->disableOriginalConstructor()
            ->getMock();
        $sites = $this->getMockBuilder('Xhgui_Sites')
            ->disableOriginalConstructor()
            ->setMethods(array('getAvailable'))
            ->getMock();
        $availableSites = array('example.com', 'example.org');
        $sites
            ->expects($this->once())
            ->method('getAvailable')
            ->will($this->returnValue($availableSites));
        $ext = new Xhgui_Twig_Extension($app, $sites);

        $this->assertEquals($availableSites, $ext->sites());
    }

    public function testCanGetCurrentSites()
    {
        $app = $this->getMockBuilder('Slim\Slim')
            ->disableOriginalConstructor()
            ->getMock();
        $sites = $this->getMockBuilder('Xhgui_Sites')
            ->disableOriginalConstructor()
            ->setMethods(array('getCurrent'))
            ->getMock();
        $sites
            ->expects($this->once())
            ->method('getCurrent')
            ->will($this->returnValue('example.com'));
        $ext = new Xhgui_Twig_Extension($app, $sites);

        $this->assertEquals('example.com', $ext->site());
    }

    public function testCanBuildSitesEnabledUrls()
    {
        $app = $this->getMockBuilder('Slim\Slim')
            ->disableOriginalConstructor()
            ->setMethods(array('urlFor'))
            ->getMock();
        $app
            ->expects($this->once())
            ->method('urlFor')
            ->with($this->equalTo('routeName'), $this->equalTo(array('site' => 'example.com')))
            ->will($this->returnValue('/example.com/view/run'));
        $sites = $this->getMockBuilder('Xhgui_Sites')
            ->disableOriginalConstructor()
            ->getMock();
        $ext = new Xhgui_Twig_Extension($app, $sites);

        $this->assertEquals(
            '/example.com/view/run',
            $ext->siteUrl('routeName', array('site' => 'example.com'))
        );
    }
}
