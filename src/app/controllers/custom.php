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

$app->post('/custom/query', function () use ($app) {
    $request = $app->request();
    $response = $app->response();
    $response['Content-Type'] = 'application/json';

    $query = json_decode($request->post('query'));
    $error = array();
    if (is_null($query)) {
        $error['query'] = json_last_error();
    }

    $retrieve = json_decode($request->post('retrieve'));
    if (is_null($retrieve)) {
        $error['retrieve'] = json_last_error();
    }

    if (count($error) > 0) {
        $json = json_encode(array('error' => $error));
        return $response->body($json);
    }

    $perPage = $app->config('page.limit');
    $res = $app->db->results->find($query, $retrieve)
        ->limit($perPage);
    $r = iterator_to_array($res);

    return $response->body(json_encode($r));
})->name('custom.query');
