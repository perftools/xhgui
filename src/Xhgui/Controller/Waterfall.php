<?php

use Slim\Slim;

class Xhgui_Controller_Waterfall extends Xhgui_Controller
{
    /**
     * @var Xhgui_Profiles
     */
    protected $profiles;

    /**
     * Xhgui_Controller_Waterfall constructor.
     * @param Slim $app
     * @param Xhgui_Profiles $profiles
     */
    public function __construct(Slim $app, Xhgui_Profiles $profiles)
    {
        parent::__construct($app);
        $this->profiles = $profiles;
    }

    /**
     *
     */
    public function index()
    {
        $request = $this->app->request();
        $filter = Xhgui_Storage_Filter::fromRequest($request);

        $result = $this->profiles->getAll($filter);

        $paging = array(
            'total_pages' => $result['totalPages'],
            'page' => $result['page'],
            'sort' => 'asc',
            'direction' => $result['direction']
        );

        $this->_template = 'waterfall/list.twig';
        $this->set(array(
            'runs' => $result['results'],
            'search' => $filter->toArray(),
            'paging' => $paging,
            'base_url' => 'waterfall.list',
        ));
    }

    /**
     *
     */
    public function query()
    {
        $request = $this->app->request();
        $response = $this->app->response();
        $filter = Xhgui_Storage_Filter::fromRequest($request);

        $result = $this->profiles->getAll($filter);

        $datas = array();
        /** @var Xhgui_Profile $r */
        foreach ($result['results'] as $r) {
            $duration = $r->get('main()', 'wt');
            $start = $r->getMeta('SERVER.REQUEST_TIME_FLOAT');
            $title = $r->getMeta('url');
            $datas[] = array(
                'id' => (string)$r->getId(),
                'title' => $title,
                'start' => $start * 1000,
                'duration' => $duration / 1000 // Convert to correct scale
            );
        }
        $response->body(json_encode($datas));
        $response['Content-Type'] = 'application/json';
    }

}
