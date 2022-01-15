<?php

namespace XHGui\Controller;

use Exception;
use Slim\App;
use Slim\Http\Response;
use Slim\Http\Request;
use XHGui\AbstractController;
use XHGui\Options\SearchOptions;
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
    
        $this->flash  = $this->app->getContainer()->get('flash');
        $this->router = $this->app->getContainer()->get('router');
    }

    public function index(Request $request, Response $response): void
    {
        $search = [];
        $keys = ['date_start', 'date_end', 'url'];
        foreach ($keys as $key) {
            if ($request->getQueryParam($key)) {
                $search[$key] = $request->getQueryParam($key);
            }
        }
        $sort = $request->getQueryParam('sort');

        $result = $this->searcher->getAll(new SearchOptions([
            'sort' => $sort,
            'page' => (int)$request->getQueryParam('page', SearcherInterface::DEFAULT_PAGE),
            'direction' => $request->getQueryParam('direction'),
            'perPage' => (int)$this->config('page.limit'),
            'conditions' => $search,
            'projection' => true,
        ]));

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
        ]);
    }

    public function view(Request $request, Response $response): void
    {
        $detailCount = $this->config('detail.count');
        $result = $this->searcher->get($request->getQueryParam('id'));

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

        if (false !== $request->getQueryParam(self::FILTER_ARGUMENT_NAME, false)) {
//            $profile = $result->sort('ewt', $result->filter($result->getProfile(), $this->getFilters()));
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
//    protected function getFilters()
    protected function getFilters(Request $request)
    {
        $filterString = $request->getQueryParam(self::FILTER_ARGUMENT_NAME);
        if (strlen($filterString) > 1 && $filterString !== 'true') {
            $filters = array_map('trim', explode(',', $filterString));
        } else {
            $filters = $this->config('run.view.filter.names');
        }

        return $filters;
    }

    public function deleteForm(Request $request): void
    {
        $id = $request->getQueryParam('id');
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

    public function deleteSubmit(Request $request, Response $response): Response
    {
        $id = $request->getParsedBodyParam('id');
        // Don't call profilers->delete() unless $id is set,
        // otherwise it will turn the null into a MongoId and return "Successful".
        if (!is_string($id) || !strlen($id)) {
            // Form checks this already,
            // only reachable by handcrafted or malformed requests.
            throw new Exception('The "id" parameter is required.');
        }

        // Delete the profile run.
        $this->searcher->delete($id);

        $this->flash->addMessage('success', 'Deleted profile ' . $id);

        return $response->withRedirect($this->router->pathFor('home'));
    }

    public function deleteAllForm(): void
    {
        $this->render('runs/delete-all-form.twig');
    }

    public function deleteAllSubmit(Request $request, Response $response): Response
    {
        // Delete all profile runs.
        $this->searcher->truncate();
    
        $this->flash->addMessage('success', 'Deleted all profiles');

        return $response->withRedirect($this->router->pathFor('home'));
        
    }

    public function url(Request $request): void
    {
        $pagination = [
            'sort' => $request->getQueryParam('sort'),
            'direction' => $request->getQueryParam('direction'),
            'page' => $request->getQueryParam('page'),
            'perPage' => $this->config('page.limit'),
        ];

        $search = [];
        $keys = ['date_start', 'date_end', 'limit', 'limit_custom'];
        foreach ($keys as $key) {
            $search[$key] = $request->getQueryParam($key);
        }

        $runs = $this->searcher->getForUrl(
            $request->getQueryParam('url'),
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
            $request->getQueryParam('url'),
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
            'url' => $request->getQueryParam('url'),
            'chart_data' => $chartData,
            'search' => array_merge($search, ['url' => $request->getQueryParam('url')]),
        ]);
    }

    public function compare(Request $request): void
    {
        $baseRun = $headRun = $candidates = $comparison = null;
        $paging = [];

        if ($request->getQueryParam('base')) {
            $baseRun = $this->searcher->get($request->getQueryParam('base'));
        }

        if ($baseRun && !$request->getQueryParam('head')) {
            $pagination = [
                'direction' => $request->getQueryParam('direction'),
                'sort' => $request->getQueryParam('sort'),
                'page' => $request->getQueryParam('page'),
                'perPage' => $this->config('page.limit'),
            ];
            $candidates = $this->searcher->getForUrl(
                $baseRun->getMeta('simple_url'),
                $pagination
            );

            $paging = [
                'total_pages' => $candidates['totalPages'],
                'sort' => $pagination['sort'],
                'page' => $candidates['page'],
                'direction' => $candidates['direction'],
            ];
        }

        if ($request->getQueryParam('head')) {
            $headRun = $this->searcher->get($request->getQueryParam('head'));
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
                'base' => $request->getQueryParam('base'),
                'head' => $request->getQueryParam('head'),
            ],
        ]);
    }

    public function symbol(Request $request): void
    {
        $id = $request->getQueryParam('id');
        $symbol = $request->getQueryParam('symbol');

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
        $id = $request->getQueryParam('id');
        $threshold = $request->getQueryParam('threshold');
        $symbol = $request->getQueryParam('symbol');
        $metric = $request->getQueryParam('metric');

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
        $profile = $this->searcher->get($request->getQueryParam('id'));

        $this->render('runs/callgraph.twig', [
            'profile' => $profile,
        ]);
    }

    public function callgraphData(Request $request, Response &$response) : Response
    {
        $profile = $this->searcher->get($request->getQueryParam('id'));
        $metric = $request->getQueryParam('metric') ?: 'wt';
        $threshold = (float)$request->getQueryParam('threshold') ?: 0.01;
        $callgraph = $profile->getCallgraph($metric, $threshold);
    
        $response_body = $response->getBody();
        $response_body->write(json_encode($callgraph));
    
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function callgraphDataDot(Request $request, Response $response): Response
    {
        $profile = $this->searcher->get($request->getQueryParam('id'));
        $metric = $request->getQueryParam('metric') ?: 'wt';
        $threshold = (float)$request->getQueryParam('threshold') ?: 0.01;
        $callgraph = $profile->getCallgraphNodes($metric, $threshold);
    
        $response_body = $response->getBody();
        $response_body->write(json_encode($callgraph));
    
        return $response->withHeader('Content-Type', 'application/json');
    }
}
