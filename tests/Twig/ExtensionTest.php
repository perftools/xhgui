<?php

namespace XHGui\Test\Twig;

use Slim\App;
use Slim\Http\Environment;
use XHGui\Test\TestCase;
use XHGui\Twig\TwigExtension;

class ExtensionTest extends TestCase
{
    /** @var TwigExtension */
    private $ext;

    public function setUp(): void
    {
        parent::setUp();
        $app = new App();
        $app->get('/test', static function (): void {
        })->setName('test');
        $this->ext = new TwigExtension($app);
    }

    public function testFormatBytes(): void
    {
        $result = $this->ext->formatBytes(2999);
        $expected = '2,999&nbsp;<span class="units">bytes</span>';
        $this->assertEquals($expected, $result);
    }

    public function testFormatTime(): void
    {
        $result = $this->ext->formatTime(2999);
        $expected = '2,999&nbsp;<span class="units">µs</span>';
        $this->assertEquals($expected, $result);
    }

    public function makePercentProvider(): array
    {
        return [
            [
                10,
                100,
                '10 <span class="units">%</span>',
            ],
            [
                0.5,
                100,
                '1 <span class="units">%</span>',
            ],
            [
                100,
                0,
                '0 <span class="units">%</span>',
            ],
        ];
    }

    /**
     * @dataProvider makePercentProvider
     */
    public function testMakePercent($value, $total, $expected): void
    {
        $result = $this->ext->makePercent($value, $total, $total);
        $this->assertEquals($expected, $result);
    }

    public static function urlProvider(): array
    {
        return [
            // simple no query string
            [
                'test',
                null,
                '/test',
            ],
            // simple with query string
            [
                'test',
                ['test' => 'value'],
                '/test?test=value',
            ],
        ];
    }

    /**
     * @dataProvider urlProvider
     */
    public function testUrl($url, $query, $expected): void
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

    public function testStaticUrlNoIndexPhp(): void
    {
        Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'PHP_SELF' => '/index.php',
            'REQUEST_URI' => '/',
        ]);
        $result = $this->ext->staticUrl('css/bootstrap.css');
        $this->assertEquals('/css/bootstrap.css', $result);
    }

    public function testStaticUrlWithIndexPhp(): void
    {
        Environment::mock([
            'SCRIPT_NAME' => '/xhgui/webroot/index.php',
            'PHP_SELF' => '/xhgui/webroot/index.php/',
            'REQUEST_URI' => '/xhgui/webroot/index.php/',
        ]);
        $result = $this->ext->staticUrl('css/bootstrap.css');
        $this->assertEquals('/xhgui/webroot/css/bootstrap.css', $result);
    }
}
