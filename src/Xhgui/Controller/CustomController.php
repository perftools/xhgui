<?php

namespace XHGui\Controller;

use Slim\Slim;
use XHGui\Searcher\SearcherInterface;
use XHGui\AbstractController;

class CustomController extends AbstractController
{
    /**
     * @var SearcherInterface
     */
    protected $searcher;

    public function __construct(Slim $app, SearcherInterface $searcher)
    {
        parent::__construct($app);
        $this->searcher = $searcher;
    }

    public function get()
    {
        $this->_template = 'custom/create.twig';
    }

    public function help()
    {
        $request = $this->app->request();
        if ($request->get('id')) {
            $res = $this->searcher->get($request->get('id'));
        } else {
            $res = $this->searcher->latest();
        }
        $this->_template = 'custom/help.twig';
        $this->set([
            'data' => print_r($res->toArray(), 1),
        ]);
    }

    public function query()
    {
        $request = $this->app->request();
        $response = $this->app->response();
        $response['Content-Type'] = 'application/json';

        $query = json_decode($request->post('query'), true);
        $error = [];
        if (null === $query) {
            $error['query'] = json_last_error();
        }

        $retrieve = json_decode($request->post('retrieve'), true);
        if (null === $retrieve) {
            $error['retrieve'] = json_last_error();
        }

        if (count($error) > 0) {
            $json = json_encode(['error' => $error]);

            return $response->body($json);
        }

        $perPage = $this->app->config('page.limit');

        $res = $this->searcher->query($query, $perPage, $retrieve);

        return $response->body(json_encode($res));
    }
}
