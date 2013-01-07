<?php
require dirname(__DIR__) . '/bootstrap.php';

xhprof_enable(XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY);

$db = new Xhgui_Db();

$result = $db->getAll(array(
    'sort' => isset($_GET['sort']) ? $_GET['sort'] : null,
    'page' => isset($_GET['page']) ? $_GET['page'] : null,
    'perPage' => Xhgui_Config::read('page.limit'),
));

$res = $result['results'];
$page = $result['page'];
$totalPages = $result['totalPages'];
$sort = $result['sort'];


$template = load_template('runs/list.twig');
echo $template->render(array(
    'runs' => $res,
    'page' => $page,
    'sort' => $sort,
    'total_pages' => $totalPages,
    'date_format' => Xhgui_Config::read('date.format')
));
flush();

//Store results


function _xhGetMeta()
{
    $meta = array(
        'url' => $_SERVER['REQUEST_URI'],
        'SERVER' => $_SERVER,
        'get' => $_GET,
        'env' => $_ENV,
        'simple_url' => simpleUrl($_SERVER['REQUEST_URI']),
    );
    return $meta;
}
$profile = xhprof_disable();
$data['meta'] = _xhGetMeta();
$data['profile'] = $profile;

$m = new Mongo();
$db = $m->xhprof;
$collection = $db->results;
$collection->insert($data);
