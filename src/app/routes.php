<?php
/**
 * Routes file for controller objects.
 */

// Profile Runs routes

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
