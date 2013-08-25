<?php

class Xhgui_Controller_Waterfall
{
    protected $_app;
    protected $_profiles;

    public function __construct($app, $profiles)
    {
        $this->_app = $app;
        $this->_profiles = $profiles;
    }

    public function index()
    {
        $request = $this->_app->request();
        $search = array();
        $keys = array("remote_addr", 'request_start', 'request_end');
        foreach ($keys as $key) {
            if ($request->get($key)) {
                $search[$key] = $request->get($key);
            }
        }
        $result = $this->_profiles->getAll(array(
            'sort' => 'time',
            'direction' => 'asc',
            'conditions' => $search,
            'projection' => true
        ));

        $paging = array(
            'total_pages' => $result['totalPages'],
            'page' => $result['page'],
            'sort' => 'asc',
            'direction' => $result['direction']
        );

        $this->_app->render('waterfall/list.twig', array(
            'runs' => $result['results'],
            'search' => $search,
            'paging' => $paging,
            'base_url' => 'waterfall.list',
        ));
    }

    public function query()
    {
        $request = $this->_app->request();
        $response = $this->_app->response();
        $search = array();
        $keys = array("remote_addr", 'request_start', 'request_end');
        foreach ($keys as $key) {
            $search[$key] = $request->get($key);
        }
        $result = $this->_profiles->getAll(array(
            'sort' => 'time',
            'direction' => 'asc',
            'conditions' => $search,
            'projection' => TRUE
        ));
        $datas = array();
        foreach ($result['results'] as $r) {
            $meta = $r->getMeta();
            $duration = $r->get('main()', 'wt');
            $start = $r->getMeta('SERVER.REQUEST_TIME');
            $end = $start + ($duration / 1000000);
            $title = $r->getMeta('url');
            $datas[] = array(
                'id' => (string)$r->getId(),
                'title' => $title,
                'start' => ($start + rand(1,1000) / 1000) * 1000,
                'duration' => $duration / 1000      //Convert to correct scale
            );
        }
        $response->body(json_encode($datas));
        $response['Content-Type'] = 'application/json';
    }

}
