<?php

use Slim\Slim;

class Xhgui_Controller
{
    protected $_templateVars = array();
    protected $_template = null;

    /**
     * @var Slim
     */
    protected $app;

    public function set($vars)
    {
        $this->_templateVars = array_merge($this->_templateVars, $vars);
    }

    public function templateVars()
    {
        return $this->_templateVars;
    }

    public function render()
    {
        $this->app->render($this->_template, $this->_templateVars);
    }

}
