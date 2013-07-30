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

$app->get('/run/view', function () use ($app) {

})->name('run.view');

$app->get('/url/view', function () use ($app) {

})->name('url.view');

$app->get('/run/compare', function () use ($app) {

})->name('run.compare');
