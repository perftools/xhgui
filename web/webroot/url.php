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


$chartData = array();
foreach ($res as $run) {
    $data = $run['profile']['main()'];
    $data += array('time' => $run['meta']['SERVER']['REQUEST_TIME'] * 1000);
    $chartData[] = $data;
}

$template = load_template('runs/url.twig');
echo $template->render(array(
    'runs' => $res,
    'url' => $_GET['url'],
    'chart_data' => $chartData,
));
