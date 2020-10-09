<?php

namespace XHGui\Controller;

use Slim\Http\Request;
use XHGui\Searcher\SearcherInterface;
use XHGui\AbstractController;
use Slim\Slim as App;

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

    public function get()
    {
        $watched = $this->searcher->getAllWatches();

        $this->_template = 'watch/list.twig';
        $this->set(['watched' => $watched]);
    }

    public function post(Request $request)
    {
        $saved = false;
        foreach ((array)$request->post('watch') as $data) {
            $saved = true;
            $this->searcher->saveWatch($data);
        }
        if ($saved) {
            $this->app->flash('success', 'Watch functions updated.');
        }
        $this->app->redirect($this->app->urlFor('watch.list'));
    }
}
