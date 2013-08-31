<?php

class Xhgui_Controller_Watch extends Xhgui_Controller
{

    protected $_app;
    protected $_watches;

    public function __construct($app, $watches)
    {
        $this->_app = $app;
        $this->_watches = $watches;
    }

    public function get()
    {
        $watched = $this->_watches->getAll();

        $this->_template = 'watch/list.twig';
        $this->set(array('watched' => $watched));
    }

    public function post()
    {
        $app = $this->_app;
        $watches = $this->_watches;

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
