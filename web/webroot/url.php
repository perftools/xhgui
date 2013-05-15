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

$search = array();
$keys = array('date_start', 'date_end');
foreach ($keys as $key) {
    $search[$key] = !empty($_GET[$key]) ? $_GET[$key] : null;
}
$runs = $profiles->getForUrl($_GET['url'], $pagination, $search);
$chartData = $profiles->getAvgsForUrl($_GET['url'], $search);

$paging = array(
    'total_pages' => $runs['totalPages'],
    'sort' => $pagination['sort'],
    'page' => $runs['page'],
    'direction' => $runs['direction']
);

$template = Xhgui_Template::load('runs/url.twig');
echo $template->render(array(
    'paging' => $paging,
    'base_url' => '/url.php',
    'runs' => $runs['results'],
    'url' => $_GET['url'],
    'chart_data' => $chartData,
    'date_format' => Xhgui_Config::read('date.format'),
    'search' => array_merge($search, array('url' => $_GET['url'])),
));
