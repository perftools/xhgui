<?php

class Twig_XhguiExtension extends Twig_Extension
{
    public function getName()
    {
        return 'xhgui';
    }

    public function getFunctions()
    {
        return array(
            'url' => new Twig_Function_Method($this, 'url'),
        );
    }

    public function getFilters()
    {
        return array(
            'simple_url' => new Twig_Filter_Function('simpleUrl'),
        );
    }

    protected function _getBase()
    {
        return dirname($_SERVER['PHP_SELF']);
    }

    /**
     * Get a URL for xhgui.
     *
     * @param string $path The file/path you want a link to
     * @param array $queryarg Additional querystring arguments.
     * @return string url.
     */
    public function url($path, $queryargs = array())
    {
        $base = $this->_getBase();
        $query = '';
        if (!empty($queryargs)) {
            $query = '?' . http_build_query($queryargs);
        }
        $path = '/' . ltrim($path, '/');
        return $base . $path . $query;
    }

}
