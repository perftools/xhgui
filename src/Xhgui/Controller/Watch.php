<?php

class Xhgui_Controller_Watch
{

    public function __construct($app, $watches)
    {
        $this->_app = $app;
        $this->_watches = $watches;
    }

    public function get()
    {
        $watched = $this->_watches->getAll();

        $this->_app->render('watch/list.twig', array(
            'watched' => $watched,
        ));
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
