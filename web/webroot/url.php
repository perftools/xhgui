<?php
require dirname(__DIR__) . '/bootstrap.php';
$perPage = Xhgui_Config::read('page.limit');

$db = Xhgui_Db::connect();
$profiles = new Xhgui_Profiles($db->results);

$pagination = array(
    'sort' => isset($_GET['sort']) ? $_GET['sort'] : null,
    'direction' => isset($_GET['direction']) ? $_GET['direction'] : null,
    'page' => isset($_GET['page']) ? $_GET['page'] : null,
    'perPage' => Xhgui_Config::read('page.limit'),
);
$runs = $profiles->getForUrl($_GET['url'], $pagination);

$chartData = $profiles->getAvgsForUrl($_GET['url']);

$template = Xhgui_Template::load('runs/url.twig');
echo $template->render(array(
    'base_url' => '/url.php',
    'runs' => $runs['results'],
    'page' => $runs['page'],
    'sort' => $runs['sort'],
    'direction' => $runs['direction'],
    'total_pages' => $runs['totalPages'],
    'url' => $_GET['url'],
    'chart_data' => $chartData,
    'date_format' => Xhgui_Config::read('date.format')
));
