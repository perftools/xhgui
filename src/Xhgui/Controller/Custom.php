<?php

class Xhgui_Controller_Custom extends Xhgui_Controller
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
        $this->_template = 'custom/create.twig';
    }

    public function help()
    {
        $request = $this->_app->request();
        if ($request->get('id')) {
            $res = $this->_profiles->get($request->get('id'));
        } else {
            $res = $this->_profiles->latest();
        }
        $this->_template = 'custom/help.twig';
        $this->set(array(
            'data' => print_r($res->toArray(), 1)
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
