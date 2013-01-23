<?php
require dirname(__DIR__) . '/bootstrap.php';
$perPage = Xhgui_Config::read('page.limit');

$db = new Xhgui_Db();

$pagination = array(
    'sort' => isset($_GET['sort']) ? $_GET['sort'] : null,
    'page' => isset($_GET['page']) ? $_GET['page'] : null,
    'perPage' => Xhgui_Config::read('page.limit'),
);
$runs = $db->getForUrl($_GET['url'], $pagination);

$chartData = $db->getAvgsForUrl($_GET['url']);

$template = load_template('runs/url.twig');
echo $template->render(array(
    'runs' => $runs['results'],
    'page' => $runs['page'],
    'sort' => $runs['sort'],
    'total_pages' => $runs['totalPages'],
    'url' => $_GET['url'],
    'chart_data' => $chartData,
    'date_format' => Xhgui_Config::read('date.format')
));
