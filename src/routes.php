<?php
/**
 * Routes for Xhgui
 */
$app->error(function (Exception $e) use ($app) {
    $app->render('error/view.twig', array(
        'message' => $e->getMessage(),
        'stack_trace' => $e->getTraceAsString(),
    ));
});

// Profile Runs routes
$app->get('/', function () use ($di) {
    $di['runController']->index();
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
    $di['watchController']->get();
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
