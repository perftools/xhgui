<?php

namespace XHGui;

use Slim\App;
use Slim\Http\Response;
use Slim\Views\Twig;

abstract class AbstractController
{
    /**
     * @var App
     */
    protected $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    protected function render(string $template, array $data = []): void
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
        return $this->app->getContainer()->get($key);
    }
}
