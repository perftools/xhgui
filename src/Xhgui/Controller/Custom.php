<?php

use Slim\Slim;

class Xhgui_Controller_Custom extends Xhgui_Controller
{
    /**
     * @var Xhgui_Profiles
     */
    protected $profiles;

    /**
     * Xhgui_Controller_Custom constructor.
     * @param Slim $app
     * @param Xhgui_Profiles $searcher
     */
    public function __construct(Slim $app, Xhgui_Profiles $profiles)
    {
        parent::__construct($app);
        $this->setProfiles($profiles);
    }

    /**
     *
     */
    public function get()
    {
        $this->_template = 'custom/create.twig';
        $this->set([
            'save_handler' => $this->app->config('save.handler'),
        ]);
    }

    /**
     *
     */
    public function help()
    {
        $request = $this->app->request();
        if ($request->get('id')) {
            $res = $this->getProfiles()->get($request->get('id'));
        } else {
            $filter = new Xhgui_Storage_Filter();
            $filter->setPerPage(1);
            $filter->setPage(0);

            $res = $this->getProfiles()->getAll($filter);
        }
        $this->_template = 'custom/help.twig';
        $this->set(array(
            'data' => print_r($res, 1)
        ));
    }

    /**
     * @return string
     */
    public function query()
    {
        $request = $this->app->request();
        $response = $this->app->response();
        $response->headers->set('Content-Type', 'application/json');

        $query = json_decode($request->post('query'), true);
        $error = array();
        if (null === $query) {
            $error['query'] = json_last_error();
        }

        $retrieve = json_decode($request->post('retrieve'), true);
        if (null === $retrieve) {
            $error['retrieve'] = json_last_error();
        }

        if (count($error) > 0) {
            $json = json_encode(array('error' => $error));
            return $response->body($json);
        }

        $perPage = $this->app->config('page.limit');

        $res = $this->getProfiles()->getStorage()->getCollection()->find($query, $retrieve)->limit($perPage);
        $r = iterator_to_array($res);
        return $response->body(json_encode($r));
    }

    /**
     * @return Xhgui_Profiles
     */
    public function getProfiles()
    {
        return $this->profiles;
    }

    /**
     * @param Xhgui_Profiles $profiles
     */
    public function setProfiles($profiles)
    {
        $this->profiles = $profiles;
    }
}
