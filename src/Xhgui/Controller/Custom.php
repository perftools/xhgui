<?php

use Slim\Slim;

class Xhgui_Controller_Custom extends Xhgui_Controller
{
    /**
     * @var Xhgui_Profiles
     */
    protected $profiles;

    public function __construct(Slim $app, Xhgui_Profiles $profiles)
    {
        $this->app = $app;
        $this->profiles = $profiles;
    }

    public function get()
    {
        $this->_template = 'custom/create.twig';
    }

    public function help()
    {
        $request = $this->app->request();
        if ($request->get('id')) {
            $res = $this->profiles->get($request->get('id'));
        } else {
            $res = $this->profiles->latest();
        }
        $this->_template = 'custom/help.twig';
        $this->set(array(
            'data' => print_r($res->toArray(), 1)
        ));
    }

    public function query()
    {
        $request = $this->app->request();
        $response = $this->app->response();
        $response['Content-Type'] = 'application/json';

        $query = json_decode($request->post('query'), true);
        $error = array();
        if (is_null($query)) {
            $error['query'] = json_last_error();
        }

        $retrieve = json_decode($request->post('retrieve'), true);
        if (is_null($retrieve)) {
            $error['retrieve'] = json_last_error();
        }

        if (count($error) > 0) {
            $json = json_encode(array('error' => $error));
            return $response->body($json);
        }

        $perPage = $this->app->config('page.limit');

        $res = $this->profiles->query($query, $retrieve)
            ->limit($perPage);
        $r = iterator_to_array($res);
        return $response->body(json_encode($r));
    }
}
