<?php

class Xhgui_Controller
{
    protected $_templateVars = array();
    protected $_template = null;

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
        $this->_app->render($this->_template, $this->_templateVars);
    }

}
