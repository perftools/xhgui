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
$app->get('/', function () use ($di) {
    $c = $di['runController'];
    $c->index();
    $c->render();
})->name('home');

$app->get('/run/view', function () use ($di) {
    $di['runController']->view();
})->name('run.view');

$app->get('/url/view', function () use ($di) {
    $di['runController']->url();
})->name('url.view');

$app->get('/run/compare', function () use ($di) {
    $di['runController']->compare();
})->name('run.compare');

$app->get('/run/symbol', function () use ($di) {
    $di['runController']->symbol();
})->name('run.symbol');

$app->get('/run/callgraph', function () use ($di) {
    $di['runController']->callgraph();
})->name('run.callgraph');


// Watch function routes.
$app->get('/watch', function () use ($di) {
    $c = $di['watchController'];
    $c->get();
    $c->render();
})->name('watch.list');

$app->post('/watch', function () use ($di) {
    $c = $di['watchController'];
    $c->post();
    $c->render();
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

