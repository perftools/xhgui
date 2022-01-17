<?php

namespace XHGui\Controller;

use Slim\App;
use XHGui\AbstractController;
use XHGui\RequestProxy as Request;
use XHGui\Searcher\SearcherInterface;

class WatchController extends AbstractController
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
        $watched = $this->searcher->getAllWatches();

        $this->render('watch/list.twig', ['watched' => $watched]);
    }

    public function post(Request $request): void
    {
        $saved = false;
        foreach ((array)$request->post('watch') as $data) {
            $saved = true;
            $this->searcher->saveWatch($data);
        }
        if ($saved) {
            $this->flashSuccess('Watch functions updated.');
        }

        $this->redirectTo('watch.list');
    }
}
