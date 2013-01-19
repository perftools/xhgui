<?php
require dirname(__DIR__) . '/bootstrap.php';
$perPage = Xhgui_Config::read('page.limit');

$db = new Xhgui_Db();
$res = $db->getForUrl($_GET['url'], $perPage);

$chartData = $db->getAvgsForUrl($_GET['url']);

$template = load_template('runs/url.twig');
echo $template->render(array(
    'runs' => $res,
    'url' => $_GET['url'],
    'chart_data' => $chartData,
    'date_format' => Xhgui_Config::read('date.format')
));
