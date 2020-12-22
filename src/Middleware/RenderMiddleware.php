<?php

namespace XHGui\Middleware;

use Slim\Middleware;

class RenderMiddleware extends Middleware
{
    public function call()
    {
        $app = $this->app;

        // Run the controller action/route function
        $this->next->call();

        // Render the template.
        if (isset($app->controller)) {
            /** @see AbstractController */
            $app->controller->renderView();
        }
    }
}
