<?php

namespace XHGui\Controller;

use Exception;
use Slim\App;
use XHGui\AbstractController;
use XHGui\Options\SearchOptions;
use XHGui\RequestProxy as Request;
use XHGui\Searcher\SearcherInterface;

class RunController extends AbstractController
{
    /**
     * HTTP GET attribute name for comma separated filters
     */
    private const FILTER_ARGUMENT_NAME = 'filter';

    /**
     * @var SearcherInterface
     */
    private $searcher;

    public function __construct(App $app, SearcherInterface $searcher)
    {
        parent::__construct($app);
        $this->searcher = $searcher;
    }

    public function index(Request $request): void
    {
        $search = [];
        $keys = ['date_start', 'date_end', 'server_name', 'url'];
        foreach ($keys as $key) {
            if ($request->get($key)) {
                $search[$key] = $request->get($key);
            }
        }
        $sort = $request->get('sort');

        $result = $this->searcher->getAll(new SearchOptions([
            'sort' => $sort,
            'page' => (int)$request->get('page', SearcherInterface::DEFAULT_PAGE),
            'direction' => $request->get('direction'),
            'perPage' => (int)$this->config('page.limit'),
            'conditions' => $search,
            'projection' => true,
        ]));

        $serverNames = $this->searcher->getAllServerNames();

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
            'direction' => $result['direction'],
        ];

        $this->render('runs/list.twig', [
            'paging' => $paging,
            'base_url' => 'home',
            'runs' => $result['results'],
            'search' => $search,
            'has_search' => implode('', $search) !== '',
            'title' => $title,
            'server_names' => $serverNames,
        ]);
    }

    public function view(Request $request): void
    {
        $detailCount = $this->config('detail.count');
        $result = $this->searcher->get($request->get('id'));

        $result->calculateSelf();

        // Self wall time graph
        $timeChart = $result->extractDimension('ewt', $detailCount);

        // Memory Block
        $memoryChart = $result->extractDimension('emu', $detailCount);

        // Watched Functions Block
        $watchedFunctions = [];
        foreach ($this->searcher->getAllWatches() as $watch) {
            $matches = $result->getWatched($watch['name']);
            if ($matches) {
                $watchedFunctions = array_merge($watchedFunctions, $matches);
            }
        }

        if (false !== $request->get(self::FILTER_ARGUMENT_NAME, false)) {
            $profile = $result->sort('ewt', $result->filter($result->getProfile(), $this->getFilters($request)));
        } else {
            $profile = $result->sort('ewt', $result->getProfile());
        }

        $this->render('runs/view.twig', [
            'profile' => $profile,
            'result' => $result,
            'wall_time' => $timeChart,
            'memory' => $memoryChart,
            'watches' => $watchedFunctions,
        ]);
    }

    /**
     * @return array
     */
    private function getFilters($request)
    {
        $filterString = $request->get(self::FILTER_ARGUMENT_NAME);
        if (strlen($filterString) > 1 && $filterString !== 'true') {
            $filters = array_map('trim', explode(',', $filterString));
        } else {
            $filters = $this->config('run.view.filter.names');
        }

        return $filters;
    }

    public function deleteForm(Request $request): void
    {
        $id = $request->get('id');
        if (!is_string($id) || !strlen($id)) {
            throw new Exception('The "id" parameter is required.');
        }

        // Get details
        $result = $this->searcher->get($id);

        $this->render('runs/delete-form.twig', [
            'run_id' => $id,
            'result' => $result,
        ]);
    }

    public function deleteSubmit(Request $request): void
    {
        $id = $request->post('id');
        // Don't call profilers->delete() unless $id is set,
        // otherwise it will turn the null into a MongoId and return "Successful".
        if (!is_string($id) || !strlen($id)) {
            // Form checks this already,
            // only reachable by handcrafted or malformed requests.
            throw new Exception('The "id" parameter is required.');
        }

        // Delete the profile run.
        $this->searcher->delete($id);

        $this->flashSuccess('Deleted profile ' . $id);
        $this->redirectTo('home');
    }

    public function deleteAllForm(): void
    {
        $this->render('runs/delete-all-form.twig');
    }

    public function deleteAllSubmit(): void
    {
        // Delete all profile runs.
        $this->searcher->truncate();

        $this->flashSuccess('Deleted all profiles');
        $this->redirectTo('home');
    }

    public function url(Request $request): void
    {
        $pagination = [
            'sort' => $request->get('sort'),
            'direction' => $request->get('direction'),
            'page' => $request->get('page'),
            'perPage' => $this->config('page.limit'),
        ];

        $search = [];
        $keys = ['date_start', 'date_end', 'limit', 'limit_custom', 'server_name'];
        foreach ($keys as $key) {
            $search[$key] = $request->get($key);
        }

        $runs = $this->searcher->getForUrl(
            $request->get('url'),
            $pagination,
            $search
        );

        if (isset($search['limit_custom']) &&
            strlen($search['limit_custom']) > 0 &&
            $search['limit_custom'][0] === 'P'
        ) {
            $search['limit'] = $search['limit_custom'];
        }

        $chartData = $this->searcher->getPercentileForUrl(
            90,
            $request->get('url'),
            $search
        );

        $paging = [
            'total_pages' => $runs['totalPages'],
            'sort' => $pagination['sort'],
            'page' => $runs['page'],
            'direction' => $runs['direction'],
        ];

        $this->render('runs/url.twig', [
            'paging' => $paging,
            'base_url' => 'url.view',
            'runs' => $runs['results'],
            'url' => $request->get('url'),
            'chart_data' => $chartData,
            'search' => array_merge($search, ['url' => $request->get('url')]),
        ]);
    }

    public function compare(Request $request): void
    {
        $baseRun = $headRun = $candidates = $comparison = null;
        $paging = [];

        if ($request->get('base')) {
            $baseRun = $this->searcher->get($request->get('base'));
        }

        if ($baseRun && !$request->get('head')) {
            $pagination = [
                'direction' => $request->get('direction'),
                'sort' => $request->get('sort'),
                'page' => $request->get('page'),
                'perPage' => $this->config('page.limit'),
            ];
            $candidates = $this->searcher->getForUrl(
                $baseRun->getMeta('simple_url'),
                $pagination,
                ['server_name' => $baseRun->getMeta('SERVER.SERVER_NAME')]
            );

            $paging = [
                'total_pages' => $candidates['totalPages'],
                'sort' => $pagination['sort'],
                'page' => $candidates['page'],
                'direction' => $candidates['direction'],
            ];
        }

        if ($request->get('head')) {
            $headRun = $this->searcher->get($request->get('head'));
        }

        if ($baseRun && $headRun) {
            $comparison = $baseRun->compare($headRun);
        }

        $this->render('runs/compare.twig', [
            'base_url' => 'run.compare',
            'base_run' => $baseRun,
            'head_run' => $headRun,
            'candidates' => $candidates,
            'url_params' => $request->getQueryParams(),
            'comparison' => $comparison,
            'paging' => $paging,
            'search' => [
                'base' => $request->get('base'),
                'head' => $request->get('head'),
            ],
        ]);
    }

    public function symbol(Request $request): void
    {
        $id = $request->get('id');
        $symbol = $request->get('symbol');

        $profile = $this->searcher->get($id);
        $profile->calculateSelf();
        [$parents, $current, $children] = $profile->getRelatives($symbol);

        $this->render('runs/symbol.twig', [
            'symbol' => $symbol,
            'id' => $id,
            'main' => $profile->get('main()'),
            'parents' => $parents,
            'current' => $current,
            'children' => $children,
        ]);
    }

    public function symbolShort(Request $request): void
    {
        $id = $request->get('id');
        $threshold = $request->get('threshold');
        $symbol = $request->get('symbol');
        $metric = $request->get('metric');

        $profile = $this->searcher->get($id);
        $profile->calculateSelf();
        [$parents, $current, $children] = $profile->getRelatives($symbol, $metric, $threshold);

        $this->render('runs/symbol-short.twig', [
            'symbol' => $symbol,
            'id' => $id,
            'main' => $profile->get('main()'),
            'parents' => $parents,
            'current' => $current,
            'children' => $children,
        ]);
    }

    public function callgraph(Request $request): void
    {
        $profile = $this->searcher->get($request->get('id'));

        $this->render('runs/callgraph.twig', [
            'profile' => $profile,
        ]);
    }

    public function callgraphData(Request $request)
    {
        $profile = $this->searcher->get($request->get('id'));
        $metric = $request->get('metric') ?: 'wt';
        $threshold = (float)$request->get('threshold') ?: 0.01;

        return $profile->getCallgraph($metric, $threshold);
    }

    public function callgraphDataDot(Request $request)
    {
        $profile = $this->searcher->get($request->get('id'));
        $metric = $request->get('metric') ?: 'wt';
        $threshold = (float)$request->get('threshold') ?: 0.01;

        return $profile->getCallgraphNodes($metric, $threshold);
    }
}
