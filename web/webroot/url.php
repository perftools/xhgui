<?php
require dirname(__DIR__) . '/bootstrap.php';

$m = new Mongo();
$db = $m->xhprof;
$collection = $db->results;

$perPage = Xhgui_Config::read('page.limit');

$res = $collection->find(array(
        'meta.simple_url' => $_GET['url']
    ))
    ->sort(array("meta.SERVER.REQUEST_TIME" => -1))
    ->limit($perPage);


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
    'date_format' => Xhgui_Config::read('date.format')
));
