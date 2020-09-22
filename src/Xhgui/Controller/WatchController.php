<?php

namespace XHGui\Controller;

use Slim\Slim;
use XHGui\Searcher\SearcherInterface;
use XHGui\AbstractController;

class WatchController extends AbstractController
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
        $watched = $this->searcher->getAllWatches();

        $this->_template = 'watch/list.twig';
        $this->set(['watched' => $watched]);
    }

    public function post()
    {
        $saved = false;
        $request = $this->app->request();
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
