<?php
/**
 * Routes file for controller objects.
 */

// Watch function routes.
$app->get('/watch', function () use ($di) {
    $di['watchController']->get();
})->name('watch.list');

$app->post('/watch', function () use ($di) {
    $di['watchController']->post();
})->name('watch.save');
