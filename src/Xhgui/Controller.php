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
    public function set(array $vars = array())
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
        // render body only if template is set. Useful for ajax/json response.
        if (!empty($this->_template)) {
            // assign application settings to template variable named config.
            $container = $this->app->container->all();
            $this->_templateVars['config'] = $container['settings'];

            // We want to render the specified Twig template to the output buffer.
            // The simplest way to do that is Slim::render, but that is not allowed
            // in middleware, because it uses Slim\View::display which prints
            // directly to the native PHP output buffer.
            // Doing that is problematic, because the HTTP headers set via $app->response()
            // must be output first, which won't happen until after the middleware
            // is completed. Output of headers and body is done by the Slim::run entry point.

            // The below is copied from Slim::render (slim/slim@2.6.3).
            // Modified to use View::fetch + Response::write, instead of View::display.
            $this->app->view->appendData($this->_templateVars);

            $body = $this->app->view->fetch($this->_template);
            $this->app->response->write($body);
        }
    }

}
