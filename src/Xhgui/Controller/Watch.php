<?php

use Slim\Slim;

class Xhgui_Controller_Watch extends Xhgui_Controller
{
    /**
     * @var Xhgui_Searcher_Interface
     */
    protected $searcher;

    public function __construct(Slim $app, Xhgui_Searcher_Interface $searcher)
    {
        parent::__construct($app);
        $this->searcher = $searcher;
    }

    public function get()
    {
        $watched = $this->searcher->getAllWatches();

        $this->_template = 'watch/list.twig';
        $this->set(array('watched' => $watched));
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
