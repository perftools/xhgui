<?php

namespace XHGui\Test;

use Psr\Http\Message\ResponseInterface;
use Slim\Views\Twig;

class TwigView extends Twig
{
    /** @var array */
    private $data;

    public function render(ResponseInterface $response, $template, $data = [])
    {
        $this->data = $data;

        return parent::render($response, $template, $data);
    }

    public function all(): array
    {
        return $this->data + $this->defaultVariables;
    }
}
