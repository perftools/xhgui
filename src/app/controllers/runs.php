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
        'conditions' => $search
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

})->name('url.view');


$app->get('/run/compare', function () use ($app) {

})->name('run.compare');


$app->get('/run/symbol', function () use ($app) {

})->name('run.symbol');

$app->get('/run/callgraph', function () use ($app) {

})->name('run.callgraph');

