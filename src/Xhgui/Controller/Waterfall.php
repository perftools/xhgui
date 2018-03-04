<?php

use Slim\Slim;

class Xhgui_Controller_Waterfall extends Xhgui_Controller
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

    public function index()
    {
        $request = $this->app->request();
        $search = array();
        $keys = array('remote_addr', 'request_start', 'request_end');
        foreach ($keys as $key) {
            if ($request->get($key)) {
                $search[$key] = trim($request->get($key));
            }
        }
        $result = $this->searcher->getAll(array(
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

        $this->_template = 'waterfall/list.twig';
        $this->set(array(
            'runs' => $result['results'],
            'search' => $search,
            'paging' => $paging,
            'base_url' => 'waterfall.list',
        ));
    }

    public function query()
    {
        $request = $this->app->request();
        $response = $this->app->response();
        $search = array();
        $keys = array('remote_addr', 'request_start', 'request_end');
        foreach ($keys as $key) {
            $search[$key] = $request->get($key);
        }
        $result = $this->searcher->getAll(array(
            'sort' => 'time',
            'direction' => 'asc',
            'conditions' => $search,
            'projection' => TRUE
        ));
        $datas = array();
        /** @var Xhgui_Profile $r */
        foreach ($result['results'] as $r) {
            $duration = $r->get('main()', 'wt');
            $start = $r->getMeta('SERVER.REQUEST_TIME_FLOAT');
            $title = $r->getMeta('url');
            $datas[] = array(
                'id' => $r->getId(),
                'title' => $title,
                'start' => $start * 1000,
                'duration' => $duration / 1000 // Convert to correct scale
            );
        }
        $response->body(json_encode($datas));
        $response['Content-Type'] = 'application/json';
    }

}
