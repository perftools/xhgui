<?php

$app->get('/custom', function () use ($app) {
    $app->render('custom/create.twig');
})->name('custom.view');

$app->get('/custom/help', function () use ($app) {
    $collection = $app->db->results;
    $res = $collection->findOne();

    $app->render('custom/help.twig', array(
        'data' => print_r($res, 1)
    ));
})->name('custom.help');
