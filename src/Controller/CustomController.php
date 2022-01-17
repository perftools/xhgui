<?php

namespace XHGui\Controller;

use Slim\App;
use XHGui\AbstractController;
use XHGui\RequestProxy as Request;
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

    public function query($query, $retrieve)
    {
        $error = [];
        $conditions = json_decode($query, true);
        if (null === $conditions) {
            $error['query'] = json_last_error();
        }

        $fields = json_decode($retrieve, true);
        if (null === $fields) {
            $error['retrieve'] = json_last_error();
        }

        if (count($error) > 0) {
            return ['error' => $error];
        }

        $perPage = $this->config('page.limit');

        return $this->searcher->query($conditions, $perPage, $fields);
    }
}
