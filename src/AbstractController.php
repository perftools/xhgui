<?php

namespace XHGui;

use Psr\Container\ContainerInterface;
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
        /** @var ContainerInterface $container */
        $container = $this->app->getContainer();
        /** @var ResponseInterface $response */
        $response = $container->get('response');
        /** @var Twig $renderer */
        $renderer = $container->get('view');

        $renderer->render($response, $template, $data);
    }

    protected function config(string $key)
    {
        return $this->app->getContainer()->get($key);
    }
}
