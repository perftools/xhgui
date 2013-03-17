<?php
require dirname(__DIR__) . '/bootstrap.php';

$db = Xhgui_Db::connect();
$profiles = new Xhgui_Profiles($db->results);

$search = array();
$keys = array('date_start', 'date_end', 'url');
foreach ($keys as $key) {
    $search[$key] = !empty($_GET[$key]) ? $_GET[$key] : null;
}
$sort = isset($_GET['sort']) ? $_GET['sort'] : null;

$result = $profiles->getAll(array(
    'sort' => $sort,
    'page' => isset($_GET['page']) ? $_GET['page'] : null,
    'perPage' => Xhgui_Config::read('page.limit'),
    'conditions' => $search
));


$title = 'Recent runs';
$titleMap = array(
    'wt' => 'Longest wall time',
    'cpu' => 'Most CPU time',
    'mu' => 'Highest memory use',
);
if (isset($titleMap[$sort])) {
    $title = $titleMap[$sort];
}

$template = Xhgui_Template::load('runs/list.twig');
echo $template->render(array(
    'runs' => $result['results'],
    'page' => $result['page'],
    'sort' => $result['sort'],
    'total_pages' => $result['totalPages'],
    'date_format' => Xhgui_Config::read('date.format'),
    'search' => $search,
    'has_search' => strlen(implode('', $search)) > 0,
    'title' => $title
));
