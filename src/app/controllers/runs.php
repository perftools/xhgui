<?php
/**
 * Routes/controllers for run related pages.
 */

// Renders a list of profile runs with sorting options.
$app->get('/', function () use ($app) {
    $request = $app->request();

    $profiles = new Xhgui_Profiles($app->db->results);

    $search = array();
    $keys = array('date_start', 'date_end', 'url');
    foreach ($keys as $key) {
        $search[$key] = $request->get($key);
    }
    $sort = $request->get('sort');


    $result = $profiles->getAll(array(
        'sort' => $sort,
        'page' => $request->get('page'),
        'direction' => $request->get('direction'),
        'perPage' => $app->config('page.limit'),
        'conditions' => $search,
        'projection' => TRUE

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

    $app->render('runs/list.twig', array(
        'paging' => $paging,
        'base_url' => 'home',
        'runs' => $result['results'],
        'date_format' => $app->config('date.format'),
        'search' => $search,
        'has_search' => strlen(implode('', $search)) > 0,
        'title' => $title
    ));
})->name('home');


/**
 * Display the data about a single run.
 */
$app->get('/run/view', function () use ($app) {
    $request = $app->request();
    $detailCount = $app->config('detail.count');

    $profiles = new Xhgui_Profiles($app->db->results);
    $result = $profiles->get($request->get('id'));

    $result->calculateExclusive();

    // Exclusive wall time graph
    $timeChart = $result->extractDimension('ewt', $detailCount);

    // Memory Block
    $memoryChart = $result->extractDimension('emu', $detailCount);

    // Watched Functions Block
    $watches = new Xhgui_WatchFunctions($app->db->watches);
    $watchedFunctions = array();
    foreach ($watches->getAll() as $watch) {
        $matches = $result->getWatched($watch['name']);
        if ($matches) {
            $watchedFunctions = array_merge($watchedFunctions, $matches);
        }
    }


    $profile = $result->sort('ewt', $result->getProfile());
    $app->render('runs/view.twig', array(
        'profile' => $profile,
        'result' => $result,
        'wall_time' => $timeChart,
        'memory' => $memoryChart,
        'watches' => $watchedFunctions,
        'date_format' => $app->config('date_format'),
    ));
})->name('run.view');


/**
 * Display the data about a single url.
 */
$app->get('/url/view', function () use ($app) {
	$request = $app->request();
	$perPage = $app->config('page.limit');

	$db = Xhgui_Db::connect();
	$profiles = new Xhgui_Profiles($app->db->results);

	$pagination = array(
		'sort' => $request->get('sort'),
		'direction' => $request->get('direction'),
		'page' => $request->get('page'),
		'perPage' => $app->config('page.limit'),
	);

	$search = array();
	$keys = array('date_start', 'date_end');
	foreach ($keys as $key) {
		$search[$key] = $request->get($key);
	}
	$runs = $profiles->getForUrl($request->get('url'), $pagination, $search);
	$chartData = $profiles->getPercentileForUrl(90, $request->get('url'), $search);

	$paging = array(
		'total_pages' => $runs['totalPages'],
		'sort' => $pagination['sort'],
		'page' => $runs['page'],
		'direction' => $runs['direction']
	);

	$app->render('runs/url.twig', array(
		'paging' => $paging,
		'base_url' => 'url.view',
		'runs' => $runs['results'],
		'url' => $request->get('url'),
		'chart_data' => $chartData,
		'date_format' => $app->config('date.format'),
		'search' => array_merge($search, array('url' => $request->get('url'))),
	));
})->name('url.view');


// Shows the compare view between two runs.
$app->get('/run/compare', function () use ($app) {
	$request = $app->request();
	$profiles = new Xhgui_Profiles($app->db->results);

	$baseRun = $headRun = $candidates = $comparison = null;
	$paging = array();

	if ($request->get('base')) {
		$baseRun = $profiles->get($request->get('base'));
	}

	if ($baseRun && !$request->get('head')) {
		$pagination = array(
			'direction' => $request->get('direction'),
			'sort' => $request->get('sort'),
			'page' => $request->get('page'),
			'perPage' => $app->config('page.limit'),
		);
		$candidates = $profiles->getForUrl(
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
		$headRun = $profiles->get($request->get('head'));
	}

	if ($baseRun && $headRun) {
		$comparison = $baseRun->compare($headRun);
	}

	$app->render('runs/compare.twig', array(
		'base_url' => 'run.compare',
		'base_run' => $baseRun,
		'head_run' => $headRun,
		'candidates' => $candidates,
		'url_params' => $request->get(),
		'date_format' => $app->config('date.format'),
		'comparison' => $comparison,
		'paging' => $paging,
		'search' => array(
			'base' => $request->get('base'),
			'head' => $request->get('head'),
		)
	));
})->name('run.compare');


$app->get('/run/symbol', function () use ($app) {
    $request = $app->request();
    $id = $request->get('id');
    $symbol = $request->get('symbol');

    $profiles = new Xhgui_Profiles($app->db->results);

    $profile = $profiles->get($id);
    $profile->calculateExclusive();
    list($parents, $current, $children) = $profile->getRelatives($symbol);

    $app->render('runs/symbol-view.twig', array(
        'symbol' => $symbol,
        'id' => $id,
        'parents' => $parents,
        'current' => $current,
        'children' => $children,
    ));
})->name('run.symbol');

$app->get('/run/callgraph', function () use ($app) {
    $request = $app->request();
    $profiles = new Xhgui_Profiles($app->db->results);
    $profile = $profiles->get($request->get('id'));

    $app->render('runs/callgraph.twig', array(
        'profile' => $profile,
        'date_format' => $app->config('date_format'),
        'callgraph' => $profile->getCallgraph(),
    ));
})->name('run.callgraph');

