<?php

namespace XHGui;

use Slim\Http\Response;

/**
 * Class dealing with Response being mutable
 */
class ResponseProxy
{
    /** @var Response */
    private $response;

    public function __construct(Response $response = null)
    {
        $this->response = $response ?: new Response();
    }

    public function setHeader(string $name, string $value): self
    {
        $this->response = $this->response->withHeader($name, $value);

        return $this;
    }

    public function setStatus($code): self
    {
        $this->response = $this->response->withStatus($code);

        return $this;
    }

    public function redirect(string $url): self
    {
        $this->response = $this->response->withRedirect($url);

        return $this;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }

    public function write($data): self
    {
        $this->response->write($data);

        return $this;
    }

    public function writeJson($data): self
    {
        $this->response->write(json_encode($data));

        return $this;
    }
}
