<?php
/**
 * Routes for Xhgui
 */
$app->error(function (Exception $e) use ($di, $app) {
    $view = $di['view'];
    $view->parserOptions['cache'] = false;
    $view->parserExtensions = array(
        new Xhgui_Twig_Extension($app)
    );

    $app->view($view);
    $app->render('error/view.twig', array(
        'message' => $e->getMessage(),
        'stack_trace' => $e->getTraceAsString(),
    ));
});

// Profile Runs routes
$app->get('/', function () use ($di, $app) {
    $app->controller = $di['runController'];
    $app->controller->index();
})->name('home');

$app->get('/run/view', function () use ($di, $app) {
    $app->controller = $di['runController'];
    $app->controller->view();
})->name('run.view');

$app->get('/url/view', function () use ($di, $app) {
    $app->controller = $di['runController'];
    $app->controller->url();
})->name('url.view');

$app->get('/run/compare', function () use ($di, $app) {
    $app->controller = $di['runController'];
    $app->controller->compare();
})->name('run.compare');

$app->get('/run/symbol', function () use ($di, $app) {
    $app->controller = $di['runController'];
    $app->controller->symbol();
})->name('run.symbol');

$app->get('/run/callgraph', function () use ($di, $app) {
    $app->controller = $di['runController'];
    $app->controller->callgraph();
})->name('run.callgraph');


// Watch function routes.
$app->get('/watch', function () use ($di, $app) {
    $app->controller = $di['watchController'];
    $app->controller->get();
})->name('watch.list');

$app->post('/watch', function () use ($di) {
    $di['watchController']->post();
})->name('watch.save');


// Custom report routes.
$app->get('/custom', function () use ($di) {
    $di['customController']->get();
})->name('custom.view');

$app->get('/custom/help', function () use ($di) {
    $di['customController']->help();
})->name('custom.help');

$app->post('/custom/query', function () use ($di) {
    $di['customController']->query();
})->name('custom.query');


// Waterfall routes
$app->get('/waterfall', function () use ($di) {
    $di['waterfallController']->index();
})->name('waterfall.list');

$app->get('/waterfall/data', function () use ($di) {
    $di['waterfallController']->query();
})->name('waterfall.data');

