<?php

use Slim\Slim;

class Xhgui_Controller_Watch extends Xhgui_Controller
{
    /**
     * @var Xhgui_WatchFunctions
     */
    protected $watches;

    public function __construct(Slim $app, Xhgui_WatchFunctions $watches)
    {
        $this->app = $app;
        $this->watches = $watches;
    }

    public function get()
    {
        $watched = $this->watches->getAll();

        $this->_template = 'watch/list.twig';
        $this->set(array('watched' => $watched));
    }

    public function post()
    {
        $app = $this->app;
        $watches = $this->watches;

        $saved = false;
        $request = $app->request();
        foreach ((array)$request->post('watch') as $data) {
            $saved = true;
            $watches->save($data);
        }
        if ($saved) {
            $app->flash('success', 'Watch functions updated.');
        }
        $app->redirect($app->urlFor('watch.list'));
    }
}
