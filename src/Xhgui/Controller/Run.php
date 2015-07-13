<?php

use Slim\Slim;

class Xhgui_Controller_Run extends Xhgui_Controller
{

    /**
     * @var Xhgui_Profiles
     */
    private $_profiles;

    public function __construct(Slim $app, \Xhgui_Profiles $profiles, Xhgui_WatchFunctions $watches)
    {
        $this->_app = $app;
        $this->_profiles = $profiles;
        $this->_watches = $watches;
    }

    public function index()
    {
        $request = $this->_app->request();

        $search = [];
        $keys = ['date_start', 'date_end', 'url'];
        foreach ($keys as $key) {
            if ($request->get($key)) {
                $search[$key] = $request->get($key);
            }
        }
        $sort = $request->get('sort');

        $result = $this->_profiles->getAll([
            'sort' => $sort,
            'page' => $request->get('page'),
            'direction' => $request->get('direction'),
            'perPage' => $this->_app->config('page.limit'),
            'conditions' => $search,
            'projection' => true,
        ]);

        $title = 'Recent runs';
        $titleMap = [
            'wt' => 'Longest wall time',
            'cpu' => 'Most CPU time',
            'mu' => 'Highest memory use',
        ];
        if (isset($titleMap[$sort])) {
            $title = $titleMap[$sort];
        }

        $paging = [
            'total_pages' => $result['totalPages'],
            'page' => $result['page'],
            'sort' => $sort,
            'direction' => $result['direction']
        ];

        $this->_template = 'runs/list.twig';
        $this->set([
            'paging' => $paging,
            'base_url' => 'home',
            'runs' => $result['results'],
            'date_format' => $this->_app->config('date.format'),
            'search' => $search,
            'has_search' => strlen(implode('', $search)) > 0,
            'title' => $title
        ]);
    }

    public function view()
    {
        $request = $this->_app->request();
        $detailCount = $this->_app->config('detail.count');
        $result = $this->_profiles->get($request->get('id'));

        $result->calculateSelf();

        // Self wall time graph
        $timeChart = $result->extractDimension('ewt', $detailCount);

        // Memory Block
        $memoryChart = $result->extractDimension('emu', $detailCount);

        // Watched Functions Block
        $watchedFunctions = [];
        foreach ($this->_watches->getAll() as $watch) {
            $matches = $result->getWatched($watch['name']);
            if ($matches) {
                $watchedFunctions = array_merge($watchedFunctions, $matches);
            }
        }

        $profile = $result->sort('ewt', $result->getProfile());

        $this->_template = 'runs/view.twig';
        $this->set([
            'profile' => $profile,
            'result' => $result,
            'wall_time' => $timeChart,
            'memory' => $memoryChart,
            'watches' => $watchedFunctions,
            'date_format' => $this->_app->config('date.format'),
        ]);
    }

    public function url()
    {
        $request = $this->_app->request();
        $pagination = [
            'sort' => $request->get('sort'),
            'direction' => $request->get('direction'),
            'page' => $request->get('page'),
            'perPage' => $this->_app->config('page.limit'),
        ];

        $search = [];
        $keys = ['date_start', 'date_end', 'limit', 'limit_custom'];
        foreach ($keys as $key) {
            $search[$key] = $request->get($key);
        }

        $runs = $this->_profiles->getForUrl(
            $request->get('url'),
            $pagination,
            $search
        );

        if (isset($search['limit_custom']) && strlen($search['limit_custom']) > 0 && $search['limit_custom'][0] == 'P') {
            $search['limit'] = $search['limit_custom'];
        }

        $chartData = $this->_profiles->getPercentileForUrl(
            90,
            $request->get('url'),
            $search
        );

        $paging = [
            'total_pages' => $runs['totalPages'],
            'sort' => $pagination['sort'],
            'page' => $runs['page'],
            'direction' => $runs['direction']
        ];

        $this->_template = 'runs/url.twig';
        $this->set([
            'paging' => $paging,
            'base_url' => 'url.view',
            'runs' => $runs['results'],
            'url' => $request->get('url'),
            'chart_data' => $chartData,
            'date_format' => $this->_app->config('date.format'),
            'search' => array_merge($search, ['url' => $request->get('url')]),
        ]);
    }

    public function compare()
    {
        $request = $this->_app->request();

        $baseRun = $headRun = $candidates = $comparison = null;
        $paging = [];

        if ($request->get('base')) {
            $baseRun = $this->_profiles->get($request->get('base'));
        }

        if ($baseRun && !$request->get('head')) {
            $pagination = [
                'direction' => $request->get('direction'),
                'sort' => $request->get('sort'),
                'page' => $request->get('page'),
                'perPage' => $this->_app->config('page.limit'),
            ];
            $candidates = $this->_profiles->getForUrl(
                $baseRun->getMeta('simple_url'),
                $pagination
            );

            $paging = [
                'total_pages' => $candidates['totalPages'],
                'sort' => $pagination['sort'],
                'page' => $candidates['page'],
                'direction' => $candidates['direction']
            ];
        }

        if ($request->get('head')) {
            $headRun = $this->_profiles->get($request->get('head'));
        }

        if ($baseRun && $headRun) {
            $comparison = $baseRun->compare($headRun);
        }

        $this->_template = 'runs/compare.twig';
        $this->set([
            'base_url' => 'run.compare',
            'base_run' => $baseRun,
            'head_run' => $headRun,
            'candidates' => $candidates,
            'url_params' => $request->get(),
            'date_format' => $this->_app->config('date.format'),
            'comparison' => $comparison,
            'paging' => $paging,
            'search' => [
                'base' => $request->get('base'),
                'head' => $request->get('head'),
            ]
        ]);
    }

    public function symbol()
    {
        $request = $this->_app->request();
        $id = $request->get('id');
        $symbol = $request->get('symbol');

        $profile = $this->_profiles->get($id);
        $profile->calculateSelf();
        list($parents, $current, $children) = $profile->getRelatives($symbol);

        $this->_template = 'runs/symbol.twig';
        $this->set([
            'symbol' => $symbol,
            'id' => $id,
            'main' => $profile->get('main()'),
            'parents' => $parents,
            'current' => $current,
            'children' => $children,
        ]);
    }

    public function compareSymbol()
    {
        $request = $this->_app->request();
        $baseId = $request->get('base_id');
        $headId = $request->get('head_id');
        $symbol = $request->get('symbol');

        $baseRun = $this->_profiles->get($baseId);
        $headRun = $this->_profiles->get($headId);

        list($parents, $current, $children) = $baseRun->compareSymbol(
            $headRun,
            $symbol
        );

        $this->_template = 'runs/compare-symbol.twig';
        $this->set([
            'symbol' => $symbol,
            'base_run' => $baseRun,
            'head_run' => $headRun,
            'main' => $baseRun->get('main()'),
            'parents' => $parents,
            'current' => $current,
            'children' => $children,
        ]);
    }

    public function symbolShort()
    {
        $request = $this->_app->request();
        $id = $request->get('id');
        $threshold = $request->get('threshold');
        $symbol = $request->get('symbol');
        $metric = $request->get('metric');

        $profile = $this->_profiles->get($id);
        $profile->calculateSelf();
        list($parents, $current, $children) = $profile->getRelatives($symbol, $metric, $threshold);

        $this->_template = 'runs/symbol-short.twig';
        $this->set([
            'symbol' => $symbol,
            'id' => $id,
            'main' => $profile->get('main()'),
            'parents' => $parents,
            'current' => $current,
            'children' => $children,
        ]);
    }

    public function callgraph()
    {
        $request = $this->_app->request();
        $profile = $this->_profiles->get($request->get('id'));

        $this->_template = 'runs/callgraph.twig';
        $this->set([
            'profile' => $profile,
            'date_format' => $this->_app->config('date.format'),
        ]);
    }

    public function callgraphData()
    {
        $request = $this->_app->request();
        $response = $this->_app->response();
        $profile = $this->_profiles->get($request->get('id'));
        $metric = $request->get('metric') ?: 'wt';
        $threshold = (float)$request->get('threshold') ?: 0.01;
        $callgraph = $profile->getCallgraph($metric, $threshold);

        $response['Content-Type'] = 'application/json';
        return $response->body(json_encode($callgraph));
    }

    public function callgraphDataDot()
    {
        $request = $this->_app->request();
        $response = $this->_app->response();
        $profile = $this->_profiles->get($request->get('id'));
        $metric = $request->get('metric') ?: 'wt';
        $threshold = (float)$request->get('threshold') ?: 0.01;
        $callgraph = $profile->getCallgraphNodes($metric, $threshold);

        $response['Content-Type'] = 'application/json';
        return $response->body(json_encode($callgraph));
    }

}
