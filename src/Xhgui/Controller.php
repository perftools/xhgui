<?php

use Slim\Slim;

abstract class Xhgui_Controller
{
    /**
     * @var array
     */
    protected $_templateVars = array();

    /**
     * @var string|null
     */
    protected $_template = null;

    /**
     * @var Slim
     */
    protected $app;

    public function __construct(Slim $app)
    {
        $this->app = $app;
    }

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
