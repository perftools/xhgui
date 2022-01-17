<?php

namespace XHGui;

use Psr\Http\Message\ResponseInterface;
use Slim\App;
use Slim\Flash;
use Slim\Router;
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

        if (!isset($data['flash'])) {
            /** @var Flash\Messages $flash */
            $flash = $this->app->getContainer()->get('flash');
            $messages = $flash->getMessages();
            $data['flash'] = $messages;
        }

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
        $container = $this->app->getContainer();
        /** @var ResponseProxy $response */
        $response = $container->get('response.proxy');
        /** @var Router $router */
        $router = $container->get('router');

        $url = $router->pathFor($name, $params);
        $response->redirect($url);
    }

    protected function flashSuccess(string $message): void
    {
        /** @var Flash\Messages $flash */
        $flash = $this->app->getContainer()->get('flash');
        $flash->addMessage('success', $message);
    }

    protected function config(string $key)
    {
        return $this->app->getContainer()->get($key);
    }
}
