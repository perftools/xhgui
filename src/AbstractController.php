<?php

namespace XHGui;

use Psr\Http\Message\ResponseInterface;
use Slim\App;
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
        $container = $this->app->getContainer();
        /** @var ResponseInterface $response */
        $response = $container->get('response');
        /** @var Twig $renderer */
        $renderer = $container->get('view');

        $renderer->render($response, $template, $data);
    }

    /**
     * Redirect to the URL of a named route
     *
     * @param string $name The route name
     * @param array $params Associative array of URL parameters and replacement values
     */
    protected function redirectTo(string $name, array $params = []): void
    {
        $this->app->redirectTo($name, $params);
    }

    protected function flashSuccess(string $message): void
    {
        $this->app->flash('success', $message);
    }

    protected function config(string $key)
    {
        return $this->app->getContainer()->get($key);
    }
}
