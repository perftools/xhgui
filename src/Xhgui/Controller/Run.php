<?php

class Xhgui_Controller_Run extends Xhgui_Controller
{
    public function __construct($app, $profiles, $watches)
    {
        $this->_app = $app;
        $this->_profiles = $profiles;
        $this->_watches = $watches;
    }

    public function index()
    {
        $request = $this->_app->request();

        $search = array();
        $keys = array('date_start', 'date_end', 'url');
        foreach ($keys as $key) {
            if ($request->get($key)) {
                $search[$key] = $request->get($key);
            }
        }
        $sort = $request->get('sort');

        $result = $this->_profiles->getAll(array(
            'sort' => $sort,
            'page' => $request->get('page'),
            'direction' => $request->get('direction'),
            'perPage' => $this->_app->config('page.limit'),
            'conditions' => $search,
            'projection' => true,
        ));

        $title = 'Recent runs';
        $titleMap = array(
            'wt' => 'Longest wall time',
            'cpu' => 'Most CPU time',
            'mu' => 'Highest memory use',
        );
        if (isset($titleMap[$sort])) {
            $title = $titleMap[$sort];
        }

        $paging = array(
            'total_pages' => $result['totalPages'],
            'page' => $result['page'],
            'sort' => $sort,
            'direction' => $result['direction']
        );

        $this->_template = 'runs/list.twig';
        $this->set(array(
            'paging' => $paging,
            'base_url' => 'home',
            'runs' => $result['results'],
            'date_format' => $this->_app->config('date.format'),
            'search' => $search,
            'has_search' => strlen(implode('', $search)) > 0,
            'title' => $title
        ));
    }

    public function view()
    {
        $request = $this->_app->request();
        $detailCount = $this->_app->config('detail.count');
        $result = $this->_profiles->get($request->get('id'));

        $result->calculateExclusive();

        // Exclusive wall time graph
        $timeChart = $result->extractDimension('ewt', $detailCount);

        // Memory Block
        $memoryChart = $result->extractDimension('emu', $detailCount);

        // Watched Functions Block
        $watchedFunctions = array();
        foreach ($this->_watches->getAll() as $watch) {
            $matches = $result->getWatched($watch['name']);
            if ($matches) {
                $watchedFunctions = array_merge($watchedFunctions, $matches);
            }
        }

        $profile = $result->sort('ewt', $result->getProfile());
        $this->_app->render('runs/view.twig', array(
            'profile' => $profile,
            'result' => $result,
            'wall_time' => $timeChart,
            'memory' => $memoryChart,
            'watches' => $watchedFunctions,
            'date_format' => $this->_app->config('date_format'),
        ));
    }

    public function url()
    {
        $request = $this->_app->request();
        $perPage = $this->_app->config('page.limit');

        $pagination = array(
            'sort' => $request->get('sort'),
            'direction' => $request->get('direction'),
            'page' => $request->get('page'),
            'perPage' => $this->_app->config('page.limit'),
        );

        $search = array();
        $keys = array('date_start', 'date_end');
        foreach ($keys as $key) {
            $search[$key] = $request->get($key);
        }
        $runs = $this->_profiles->getForUrl(
            $request->get('url'),
            $pagination,
            $search
        );
        $percentiles = $this->_profiles->getPercentileForUrl(
            90,
            $request->get('url'),
            $search
        );
        
        $chartData = array(
            array('key' => 'wt', 'values' => array()),
            array('key' => 'cpu', 'values' => array()),
            array('key' => 'mu', 'values' => array()),
            array('key' => 'pmu', 'values' => array())
        );
        foreach ($percentiles as $percentile) {
            $tstamp = (int)(strtotime($percentile['date']).'000');
            foreach ($chartData as &$line) {
                $line['values'][] = array($tstamp, $percentile[$line['key']]);
            }
        }

        $paging = array(
            'total_pages' => $runs['totalPages'],
            'sort' => $pagination['sort'],
            'page' => $runs['page'],
            'direction' => $runs['direction']
        );

        $this->_app->render('runs/url.twig', array(
            'paging' => $paging,
            'base_url' => 'url.view',
            'runs' => $runs['results'],
            'url' => $request->get('url'),
            'chart_data' => $chartData,
            'date_format' => $this->_app->config('date.format'),
            'search' => array_merge($search, array('url' => $request->get('url'))),
        ));
    }

    public function compare()
    {
        $request = $this->_app->request();

        $baseRun = $headRun = $candidates = $comparison = null;
        $paging = array();

        if ($request->get('base')) {
            $baseRun = $this->_profiles->get($request->get('base'));
        }

        if ($baseRun && !$request->get('head')) {
            $pagination = array(
                'direction' => $request->get('direction'),
                'sort' => $request->get('sort'),
                'page' => $request->get('page'),
                'perPage' => $this->_app->config('page.limit'),
            );
            $candidates = $this->_profiles->getForUrl(
                $baseRun->getMeta('simple_url'),
                $pagination
            );

            $paging = array(
                'total_pages' => $candidates['totalPages'],
                'sort' => $pagination['sort'],
                'page' => $candidates['page'],
                'direction' => $candidates['direction']
            );
        }

        if ($request->get('head')) {
            $headRun = $this->_profiles->get($request->get('head'));
        }

        if ($baseRun && $headRun) {
            $comparison = $baseRun->compare($headRun);
        }

        $this->_app->render('runs/compare.twig', array(
            'base_url' => 'run.compare',
            'base_run' => $baseRun,
            'head_run' => $headRun,
            'candidates' => $candidates,
            'url_params' => $request->get(),
            'date_format' => $this->_app->config('date.format'),
            'comparison' => $comparison,
            'paging' => $paging,
            'search' => array(
                'base' => $request->get('base'),
                'head' => $request->get('head'),
            )
        ));
    }

    public function symbol()
    {
        $request = $this->_app->request();
        $id = $request->get('id');
        $symbol = $request->get('symbol');

        $profile = $this->_profiles->get($id);
        $profile->calculateExclusive();
        list($parents, $current, $children) = $profile->getRelatives($symbol);

        $this->_app->render('runs/symbol-view.twig', array(
            'symbol' => $symbol,
            'id' => $id,
            'parents' => $parents,
            'current' => $current,
            'children' => $children,
        ));
    }

    public function callgraph()
    {
        $request = $this->_app->request();
        $profile = $this->_profiles->get($request->get('id'));

        $this->_app->render('runs/callgraph.twig', array(
            'profile' => $profile,
            'date_format' => $this->_app->config('date_format'),
            'callgraph' => $profile->getCallgraph(),
        ));
    }

}
