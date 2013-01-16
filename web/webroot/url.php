<?php
require dirname(__DIR__) . '/bootstrap.php';
$perPage = Xhgui_Config::read('page.limit');

$db = new Xhgui_Db();
$res = $db->getForUrl($_GET['url'], $perPage);

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
