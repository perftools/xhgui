<?php

namespace XHGui\Controller;

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;
use XHGui\AbstractController;
use XHGui\Searcher\SearcherInterface;

class CustomController extends AbstractController
{
    /**
     * @var SearcherInterface
     */
    protected $searcher;

    public function __construct(App $app, SearcherInterface $searcher)
    {
        parent::__construct($app);
        $this->searcher = $searcher;
    }

    public function get(): void
    {
        $this->render('custom/create.twig');
    }

    public function help(Request $request): void
    {
        if ($request->get('id')) {
            $res = $this->searcher->get($request->get('id'));
        } else {
            $res = $this->searcher->latest();
        }
        $this->render('custom/help.twig', [
            'data' => print_r($res->toArray(), 1),
        ]);
    }

    public function query(Request $request, Response $response)
    {
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

        $perPage = $this->config('page.limit');

        $res = $this->searcher->query($query, $perPage, $retrieve);

        return $response->body(json_encode($res));
    }
}
