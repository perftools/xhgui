<?php

class Xhgui_Controller_Custom
{
    protected $_app;
    protected $_profiles;

    public function __construct($app, $profiles)
    {
        $this->_app = $app;
        $this->_profiles = $profiles;
    }

    public function get()
    {
        $this->_app->render('custom/create.twig');
    }

    public function help()
    {
        $res = $this->_profiles->latest();
        $this->_app->render('custom/help.twig', array(
            'data' => print_r($res[0]->toArray(), 1)
        ));
    }

    public function query()
    {
        $request = $this->_app->request();
        $response = $this->_app->response();
        $response['Content-Type'] = 'application/json';

        $query = json_decode($request->post('query'));
        $error = array();
        if (is_null($query)) {
            $error['query'] = json_last_error();
        }

        $retrieve = json_decode($request->post('retrieve'));
        if (is_null($retrieve)) {
            $error['retrieve'] = json_last_error();
        }

        if (count($error) > 0) {
            $json = json_encode(array('error' => $error));
            return $response->body($json);
        }

        $perPage = $this->_app->config('page.limit');

        $res = $this->_profiles->query($query, $retrieve)
            ->limit($perPage);
        $r = iterator_to_array($res);
        return $response->body(json_encode($r));
    }
}
