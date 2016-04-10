<?php

class Xhgui_Twig_Extension extends Twig_Extension
{
    protected $_app;

    public function __construct($app)
    {
        $this->_app = $app;
    }

    public function getName()
    {
        return 'xhgui';
    }

    public function getFunctions()
    {
        return array(
            'url' => new Twig_Function_Method($this, 'url'),
            'static' => new Twig_Function_Method($this, 'staticUrl'),
            'percent' => new Twig_Function_Method($this, 'makePercent', array(
                'is_safe' => array('html')
            )),
        );
    }

    public function getFilters()
    {
        return array(
            'simple_url' => new Twig_Filter_Function('Xhgui_Util::simpleUrl'),
            'as_bytes' => new Twig_Filter_Method($this, 'formatBytes', array('is_safe' => array('html'))),
            'as_time' => new Twig_Filter_Method($this, 'formatTime', array('is_safe' => array('html'))),
            'as_diff' => new Twig_Filter_Method($this, 'formatDiff', array('is_safe' => array('html'))),
            'as_percent' => new Twig_Filter_Method($this, 'formatPercent', array('is_safe' => array('html'))),
            'truncate' => new Twig_Filter_Method($this, 'truncate'),
        );
    }

    protected function _getBase()
    {
        $base = dirname($_SERVER['PHP_SELF']);
        if ($base == '/') {
            return '';
        }
        return $base;
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
     * @param string $path The file/path you want a link to
     * @param array $queryarg Additional querystring arguments.
     * @return string url.
     */
    public function url($name, $queryargs = array())
    {
        $query = '';
        if (!empty($queryargs)) {
            $query = '?' . http_build_query($queryargs);
        }
        return $this->_app->urlFor($name)  . $query;
    }

    /**
     * Get the URL for static content relative to webroot
     *
     * @param string $path The file/path you want a link to
     * @return string url.
     */
    public function staticUrl($url)
    {
        $rootUri = $this->_app->request()->getRootUri();

        // Get URL part prepending index.php
        $indexPos = strpos($rootUri, 'index.php');
        if ($indexPos > 0) {
            return substr($rootUri, 0, $indexPos) . $url;
        }
        return $rootUri . '/' . $url;
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
        $class = 'diff-same';
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
}
