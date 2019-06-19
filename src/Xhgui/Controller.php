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

    /**
     * Xhgui_Controller constructor.
     * @param Slim $app
     */
    public function __construct(Slim $app)
    {
        $this->app = $app;
    }

    /**
     * Set template variables
     *
     * @param $vars
     */
    public function set(array $vars = [])
    {
        $this->_templateVars = array_merge($this->_templateVars, $vars);
    }

    /**
     * Get all defined template variables
     *
     * @return array
     */
    public function templateVars()
    {
        return $this->_templateVars;
    }

    /**
     * Render template if template name is set.
     */
    public function render()
    {
        $container = $this->app->container->all();
        $this->_templateVars['config'] = $container['settings'];
        if (!empty($this->_template)) {
            $this->app->render($this->_template, $this->_templateVars);
        }
    }

}
