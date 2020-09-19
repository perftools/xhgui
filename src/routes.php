<?php
/**
 * Routes for Xhgui
 */
$app->error(static function (Exception $e) use ($di, $app) {
    $view = $di['view'];
    $view->parserOptions['cache'] = false;
    $view->parserExtensions = [
        new Xhgui_Twig_Extension($app),
    ];

    // Remove the controller so we don't render it.
    unset($app->controller);

    $app->view($view);
    $app->render('error/view.twig', [
        'message' => $e->getMessage(),
        'stack_trace' => $e->getTraceAsString(),
    ]);
});

// Profile Runs routes
$app->get('/', static function () use ($di, $app) {
    $app->controller = $di['runController'];
    $app->controller->index();
})->name('home');

$app->get('/run/view', static function () use ($di, $app) {
    $app->controller = $di['runController'];
    $app->controller->view();
})->name('run.view');

$app->get('/run/delete', static function () use ($di, $app) {
    $app->controller = $di['runController'];
    $app->controller->deleteForm();
})->name('run.delete.form');

$app->post('/run/delete', static function () use ($di, $app) {
    $di['runController']->deleteSubmit();
})->name('run.delete.submit');

$app->get('/run/delete_all', static function () use ($di, $app) {
    $app->controller = $di['runController'];
    $app->controller->deleteAllForm();
})->name('run.deleteAll.form');

$app->post('/run/delete_all', static function () use ($di, $app) {
    $di['runController']->deleteAllSubmit();
})->name('run.deleteAll.submit');

$app->get('/url/view', static function () use ($di, $app) {
    $app->controller = $di['runController'];
    $app->controller->url();
})->name('url.view');

$app->get('/run/compare', static function () use ($di, $app) {
    $app->controller = $di['runController'];
    $app->controller->compare();
})->name('run.compare');

$app->get('/run/symbol', static function () use ($di, $app) {
    $app->controller = $di['runController'];
    $app->controller->symbol();
})->name('run.symbol');

$app->get('/run/symbol/short', static function () use ($di, $app) {
    $app->controller = $di['runController'];
    $app->controller->symbolShort();
})->name('run.symbol-short');

$app->get('/run/callgraph', static function () use ($di, $app) {
    $app->controller = $di['runController'];
    $app->controller->callgraph();
})->name('run.callgraph');

$app->get('/run/callgraph/data', static function () use ($di, $app) {
    $di['runController']->callgraphData();
})->name('run.callgraph.data');

$app->get('/run/callgraph/dot', static function () use ($di, $app) {
    $di['runController']->callgraphDataDot();
})->name('run.callgraph.dot');

// Import route
$app->post('/run/import', static function () use ($di, $app) {
    /** @var Xhgui_Controller_Import $controller */
    $controller = $di['importController'];
    $controller->import();
})->name('run.import');

// Watch function routes.
$app->get('/watch', static function () use ($di, $app) {
    $app->controller = $di['watchController'];
    $app->controller->get();
})->name('watch.list');

$app->post('/watch', static function () use ($di) {
    $di['watchController']->post();
})->name('watch.save');

// Custom report routes.
$app->get('/custom', static function () use ($di, $app) {
    $app->controller = $di['customController'];
    $app->controller->get();
})->name('custom.view');

$app->get('/custom/help', static function () use ($di, $app) {
    $app->controller = $di['customController'];
    $app->controller->help();
})->name('custom.help');

$app->post('/custom/query', static function () use ($di) {
    $di['customController']->query();
})->name('custom.query');

// Waterfall routes
$app->get('/waterfall', static function () use ($di, $app) {
    $app->controller = $di['waterfallController'];
    $app->controller->index();
})->name('waterfall.list');

$app->get('/waterfall/data', static function () use ($di) {
    $di['waterfallController']->query();
})->name('waterfall.data');

// Metrics
$app->get('/metrics', static function () use ($di, $app) {
    $di['metricsController']->metrics();
})->name('metrics');
