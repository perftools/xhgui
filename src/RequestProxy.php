<?php

namespace XHGui;

use Slim\Http\Request;

/**
 * Class making it convenient to access request parameters
 */
class RequestProxy
{
    public function __construct(private Request $request)
    {
    }

    public function getQueryParams()
    {
        return $this->request->getQueryParams();
    }

    public function get(string $key, $default = null)
    {
        return $this->request->getQueryParam($key, $default);
    }

    public function post(string $key, $default = null)
    {
        return $this->request->getParsedBodyParam($key, $default);
    }

    public function getBody()
    {
        return $this->request->getBody();
    }

    public function getContentLength(): ?int
    {
        return $this->request->getContentLength();
    }
}
