<?php

use Slim\Router;
use Slim\Slim;

class Xhgui_Twig_Extension extends Twig_Extension
{
    /** @var Slim */
    protected $_app;
    /** @var Router */
    private $router;
    /** @var string */
    private $pathPrefix;

    public function __construct(Slim $app)
    {
        $this->_app = $app;
        $this->router = $app->router();
        $this->pathPrefix = $app->config('path.prefix');
    }

    public function getName()
    {
        return 'xhgui';
    }

    public function getFunctions()
    {
        return [
            'url' => new Twig_Function_Method($this, 'url'),
            'static' => new Twig_Function_Method($this, 'staticUrl'),
            'percent' => new Twig_Function_Method($this, 'makePercent', [
                'is_safe' => ['html']
            ]),
        ];
    }

    public function getFilters()
    {
        return [
            'simple_url' => new Twig_Filter_Function('Xhgui_Util::simpleUrl'),
            'as_bytes' => new Twig_Filter_Method($this, 'formatBytes', ['is_safe' => ['html']]),
            'as_time' => new Twig_Filter_Method($this, 'formatTime', ['is_safe' => ['html']]),
            'as_diff' => new Twig_Filter_Method($this, 'formatDiff', ['is_safe' => ['html']]),
            'as_percent' => new Twig_Filter_Method($this, 'formatPercent', ['is_safe' => ['html']]),
            'truncate' => new Twig_Filter_Method($this, 'truncate'),
        ];
    }

    public function truncate($input, $length = 50)
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
     * @param array $queryargs Additional querystring arguments.
     * @return string url.
     */
    public function url($name, $queryargs = [])
    {
        $query = '';
        if (!empty($queryargs)) {
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
     * @return string url.
     */
    public function staticUrl($path)
    {
        $rootUri = $this->pathPrefix();

        return rtrim($rootUri, '/') . '/' . $path;
    }

    public function formatBytes($value)
    {
        return number_format((float)$value) . '&nbsp;<span class="units">bytes</span>';
    }

    public function formatTime($value)
    {
        return number_format((float)$value) . '&nbsp;<span class="units">µs</span>';
    }

    public function formatDiff($value)
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

    public function makePercent($value, $total)
    {
        $value = (false === empty($total)) ? $value / $total : 0;
        return $this->formatPercent($value);
    }

    public function formatPercent($value)
    {
        return number_format((float)$value * 100, 0) . ' <span class="units">%</span>';
    }

    /**
     * @return string
     */
    private function pathPrefix()
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
