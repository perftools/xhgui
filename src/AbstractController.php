<?php

namespace XHGui;

use Slim\Http\Response;
use Slim\Slim as App;
use Slim\Views\Twig;

abstract class AbstractController
{
    /**
     * @var array
     */
    protected $_templateVars = [];

    /**
     * @var string|null
     */
    protected $_template = null;

    /**
     * @var App
     */
    protected $app;

    public function __construct(App $app)
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

    /** @see RenderMiddleware */
    public function renderView()
    {
        // We want to render the specified Twig template to the output buffer.
        // The simplest way to do that is Slim::render, but that is not allowed
        // in middleware, because it uses Slim\View::display which prints
        // directly to the native PHP output buffer.
        // Doing that is problematic, because the HTTP headers set via $app->response()
        // must be output first, which won't happen until after the middleware
        // is completed. Output of headers and body is done by the Slim::run entry point.

        // The below is copied from Slim::render (slim/slim@2.6.3).
        // Modified to use View::fetch + Response::write, instead of View::display.
        $this->render($this->_template, $this->_templateVars);
    }

    protected function render(string $template, array $data = [])
    {
        /** @var Response $response */
        $response = $this->app->response;
        /** @var Twig $renderer */
        $renderer = $this->app->view;

        $renderer->appendData($data);
        $body = $renderer->fetch($template);
        $response->write($body);
    }

    protected function config(string $key)
    {
        return $this->app->config($key);
    }
}
