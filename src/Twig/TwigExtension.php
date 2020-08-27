<?php

namespace XHGui\Twig;

use Slim\App;
use Slim\Router;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class TwigExtension extends AbstractExtension
{
    /** @var App */
    protected $_app;
    /** @var Router */
    private $router;
    /** @var string */
    private $pathPrefix;

    public function __construct(App $app)
    {
        $this->_app = $app;
        $this->router = $app->router();
        $this->pathPrefix = $app->config('path.prefix');
    }

    public function getFunctions(): array
    {
        $options = [
            'is_safe' => ['html'],
        ];

        return [
            new TwigFunction('url', [$this, 'url']),
            new TwigFunction('static', [$this, 'staticUrl']),
            new TwigFunction('percent', [$this, 'makePercent'], $options),
        ];
    }

    public function getFilters(): array
    {
        $options = [
            'is_safe' => ['html'],
        ];

        return [
            new TwigFilter('as_bytes', [$this, 'formatBytes'], $options),
            new TwigFilter('as_time', [$this, 'formatTime'], $options),
            new TwigFilter('as_diff', [$this, 'formatDiff'], $options),
            new TwigFilter('as_percent', [$this, 'formatPercent'], $options),
            new TwigFilter('truncate', [$this, 'truncate']),
        ];
    }

    public function truncate(string $input, int $length = 50): string
    {
        if (strlen($input) < $length) {
            return $input;
        }

        return substr($input, 0, $length) . "\xe2\x80\xa6";
    }

    /**
     * Get a URL for xhgui.
     *
     * @param string $name The file/path you want a link to
     * @param array|null $queryargs additional querystring arguments
     * @return string url
     */
    public function url(string $name, $queryargs = []): string
    {
        $query = '';
        if ($queryargs) {
            $query = '?' . http_build_query($queryargs);
        }

        // this is copy of \Slim\Slim::urlFor() to mix path prefix in
        // \Slim\Slim::urlFor

        return rtrim($this->pathPrefix(), '/') . $this->router->urlFor($name) . $query;
    }

    /**
     * Get the URL for static content relative to webroot
     *
     * @param string $path The file/path you want a link to
     * @return string url
     */
    public function staticUrl(string $path): string
    {
        $rootUri = $this->pathPrefix();

        return rtrim($rootUri, '/') . '/' . $path;
    }

    public function formatBytes($value): string
    {
        return number_format((float)$value) . '&nbsp;<span class="units">bytes</span>';
    }

    public function formatTime($value): string
    {
        return number_format((float)$value) . '&nbsp;<span class="units">µs</span>';
    }

    public function formatDiff($value): string
    {
        $class = $value > 0 ? 'diff-up' : 'diff-down';
        if ($value == 0) {
            $class = 'diff-same';
        }

        return sprintf(
            '<span class="%s">%s</span>',
            $class,
            number_format((float)$value)
        );
    }

    public function makePercent($value, $total): string
    {
        $value = (false === empty($total)) ? $value / $total : 0;

        return $this->formatPercent($value);
    }

    public function formatPercent($value): string
    {
        return number_format((float)$value * 100, 0) . ' <span class="units">%</span>';
    }

    private function pathPrefix(): string
    {
        if ($this->pathPrefix !== null) {
            return $this->pathPrefix;
        }

        $request = $this->_app->request();
        $rootUri = $request->getRootUri();

        // Get URL part prepending index.php
        $indexPos = strpos($rootUri, 'index.php');
        if ($indexPos > 0) {
            return substr($rootUri, 0, $indexPos);
        }

        return $rootUri;
    }
}
