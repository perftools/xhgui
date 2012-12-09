<?php
require dirname(__DIR__) . '/app/bootstrap.php';

$m = new Mongo();
$db = $m->xhprof;
$collection = $db->results;

$res = $collection->find(array(
        'meta.simple_url' => $_GET['url']
    ))
    ->sort(array("meta.SERVER.REQUEST_TIME" => -1))
    ->limit(DISPLAY_LIMIT);

$template = load_template('runs/url.twig');
echo $template->render(array(
    'runs' => $res,
    'url' => $_GET['url'],
));
