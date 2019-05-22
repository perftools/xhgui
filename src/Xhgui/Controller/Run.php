<?php

use Slim\Slim;

class Xhgui_Controller_Run extends Xhgui_Controller
{
    /**
     * @var Xhgui_Profiles
     */
    private $profiles;

    /**
     * @var \Xhgui_WatchedFunctionsStorageInterface
     */
    private $watches;

    /**
     * Xhgui_Controller_Run constructor.
     * @param Slim $app
     * @param Xhgui_Profiles $profiles
     * @param Xhgui_WatchedFunctionsStorageInterface $watches
     */
    public function __construct(Slim $app, Xhgui_Profiles $profiles, \Xhgui_WatchedFunctionsStorageInterface $watches)
    {
        parent::__construct($app);
        $this->profiles = $profiles;
        $this->watches  = $watches;
    }

    /**
     *
     */
    public function index()
    {
        $request = $this->app->request();

        $filter = Xhgui_Storage_Filter::fromRequest($request);

        $result = $this->profiles->getAll($filter);
        $title = 'Recent runs';
        $titleMap = array(
            'wt'    => 'Longest wall time',
            'cpu'   => 'Most CPU time',
            'mu'    => 'Highest memory use',
        );
        if (isset($titleMap[$filter->getSort()])) {
            $title = $titleMap[$filter->getSort()];
        }
        $paging = array(
            'total_pages'   => $result['totalPages'],
            'page'          => $result['page'],
            'sort'          => $filter->getSort(),
            'direction'     => $result['direction']
        );

        $this->_template = 'runs/list.twig';

        $this->set(array(
            'paging'        => $paging,
            'base_url'      => 'home',
            'runs'          => $result['results'],
            'date_format'   => $this->app->config('date.format'),
            'search'        => $filter->toArray(),
            'title'         => $title
        ));
    }

    /**
     *
     */
    public function view()
    {
        $request = $this->app->request();
        $detailCount = $this->app->config('detail.count');
        $result = $this->profiles->get($request->get('id'));

        $result->calculateSelf();

        // Self wall time graph
        $timeChart = $result->extractDimension('ewt', $detailCount);

        // Memory Block
        $memoryChart = $result->extractDimension('emu', $detailCount);

        // Watched Functions Block
        $watchedFunctions = array();
        foreach ($this->watches->getWatchedFunctions() as $watch) {
            $matches = $result->getWatched($watch['name']);

            if ($matches) {
                $watchedFunctions = array_merge($watchedFunctions, $matches);
            }
        }

        $profile = $result->sort('ewt', $result->getProfile());

        $this->_template = 'runs/view.twig';
        $this->set(array(
            'profile'       => $profile,
            'result'        => $result,
            'wall_time'     => $timeChart,
            'memory'        => $memoryChart,
            'watches'       => $watchedFunctions,
            'date_format'   => $this->app->config('date.format'),
        ));
    }

    /**
     * @throws Exception
     */
    public function deleteForm()
    {
        $request = $this->app->request();
        $id = $request->get('id');
        if (!is_string($id) || !strlen($id)) {
            throw new Exception('The "id" parameter is required.');
        }

        // Get details
        $result = $this->profiles->get($id);

        $this->_template = 'runs/delete-form.twig';
        $this->set(array(
            'run_id' => $id,
            'result' => $result,
        ));
    }

    /**
     * @throws Exception
     */
    public function deleteSubmit()
    {
        $request = $this->app->request();
        $id = $request->post('id');

        // Don't call profilers->delete() unless $id is set,
        // otherwise it will turn the null into a MongoId and return "Sucessful".
        if (!is_string($id) || !strlen($id)) {
            // Form checks this already,
            // only reachable by handcrafted or malformed requests.
            throw new Exception('The "id" parameter is required.');
        }

        // Delete the profile run.
        $this->profiles->delete($id);

        $this->app->flash('success', 'Deleted profile ' . $id);

        $this->app->redirect($this->app->urlFor('home'));
    }

    /**
     *
     */
    public function deleteAllForm()
    {
        $this->_template = 'runs/delete-all-form.twig';
    }

    /**
     *
     */
    public function deleteAllSubmit()
    {
        // Delete all profile runs.
        $this->profiles->truncate();

        $this->app->flash('success', 'Deleted all profiles');

        $this->app->redirect($this->app->urlFor('home'));
    }

    /**
     *
     */
    public function url()
    {
        $request = $this->app->request();

        $filter = Xhgui_Storage_Filter::fromRequest($request);
        $filter->setUrl($request->get('url'));
        $result = $this->profiles->getAll($filter);

        $chartData = $this->profiles->getPercentileForUrl(
            90,
            $request->get('url'),
            $filter
        );

        $paging = array(
            'total_pages'   => $result['totalPages'],
            'sort'          => $filter->getSort(),
            'page'          => $result['page'],
            'direction'     => $result['direction']
        );

        $this->_template = 'runs/url.twig';
        $this->set(array(
            'paging'        => $paging,
            'base_url'      => 'url.view',
            'runs'          => $result['results'],
            'url'           => $filter->getUrl(),
            'chart_data'    => $chartData,
            'date_format'   => $this->app->config('date.format'),
            'search'        => array_merge($filter->toArray(), array('url' => $request->get('url'))),
        ));
    }

    /**
     *
     */
    public function compare()
    {
        $request = $this->app->request();

        $baseRun = $headRun = $candidates = $comparison = null;
        $paging = array();

        if ($request->get('base')) {
            $baseRun = $this->profiles->get($request->get('base'));
        }

        // we have one selected but we need to list other runs.
        if ($baseRun && !$request->get('head')) {
            $filter = Xhgui_Storage_Filter::fromRequest($request);
            $filter->setUrl($baseRun->getMeta('simple_url'));

            $candidates = $this->profiles->getAll($filter);

            $paging = array(
                'total_pages'   => $candidates['totalPages'],
                'page'          => $candidates['page'],
                'sort'          => $filter->getSort(),
                'direction'     => $candidates['direction']
            );
        }

        if ($request->get('head')) {
            $headRun = $this->profiles->get($request->get('head'));
        }

        if ($baseRun && $headRun) {
            $comparison = $baseRun->compare($headRun);
        }

        $this->_template = 'runs/compare.twig';
        $this->set(array(
            'base_url'      => 'run.compare',
            'base_run'      => $baseRun,
            'head_run'      => $headRun,
            'candidates'    => $candidates,
            'url_params'    => $request->get(),
            'date_format'   => $this->app->config('date.format'),
            'comparison'    => $comparison,
            'paging'        => $paging,
            'search'        => array(
                'base' => $request->get('base'),
                'head' => $request->get('head'),
            )
        ));
    }

    /**
     *
     */
    public function symbol()
    {
        $request    = $this->app->request();
        $id         = $request->get('id');
        $symbol     = $request->get('symbol');

        $profile = $this->profiles->get($id);
        $profile->calculateSelf();
        list($parents, $current, $children) = $profile->getRelatives($symbol);

        $this->_template = 'runs/symbol.twig';
        $this->set(array(
            'symbol'    => $symbol,
            'id'        => $id,
            'main'      => $profile->get('main()'),
            'parents'   => $parents,
            'current'   => $current,
            'children'  => $children,
        ));
    }

    /**
     *
     */
    public function symbolShort()
    {
        $request    = $this->app->request();
        $id         = $request->get('id');
        $threshold  = $request->get('threshold');
        $symbol     = $request->get('symbol');
        $metric     = $request->get('metric');

        $profile = $this->profiles->get($id);
        $profile->calculateSelf();
        list($parents, $current, $children) = $profile->getRelatives($symbol, $metric, $threshold);

        $this->_template = 'runs/symbol-short.twig';
        $this->set(array(
            'symbol'    => $symbol,
            'id'        => $id,
            'main'      => $profile->get('main()'),
            'parents'   => $parents,
            'current'   => $current,
            'children'  => $children,
        ));
    }

    /**
     *
     */
    public function callgraph()
    {
        $request = $this->app->request();
        $profile = $this->profiles->get($request->get('id'));

        $this->_template = 'runs/callgraph.twig';
        $this->set(array(
            'profile'       => $profile,
            'date_format'   => $this->app->config('date.format'),
        ));
    }

    /**
     * @return string
     * @throws Exception
     */
    public function callgraphData($nodes = false)
    {
        $request    = $this->app->request();
        $response   = $this->app->response();
        $profile    = $this->profiles->get($request->get('id'));
        $metric     = $request->get('metric') ?: 'wt';
        $threshold  = (float)$request->get('threshold') ?: 0.01;

        if ($nodes) {
            $callgraph  = $profile->getCallgraphNodes($metric, $threshold);
        } else {
            $callgraph  = $profile->getCallgraph($metric, $threshold);
        }

        $response['Content-Type'] = 'application/json';
        return $response->body(json_encode($callgraph));
    }
}
