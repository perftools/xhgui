<?php
require dirname(__DIR__) . '/app/bootstrap.php';

xhprof_enable(XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY);

$m = new Mongo();
$db = $m->xhprof;
$collection = $db->results;


//Let's get results from the database
$res = $collection->find()->sort(array("meta.SERVER.REQUEST_TIME" => -1))->limit(DISPLAY_LIMIT);
$template = load_template('runs/list.twig');
echo $template->render(array(
    'runs' => $res
));

foreach($res as $result)
{
    $id = (string) $result['_id'];
    echo <<<ROW
    
ROW;
}


//Store results


function _xhGetMeta()
{
    $meta = array(
        'url' => $_SERVER['REQUEST_URI'],
        'SERVER' => $_SERVER,
        'get' => $_GET,
        'env' => $_ENV,
    );
    return $meta;
}
$profile = xhprof_disable();
$data['meta'] = _xhGetMeta();
$data['profile'] = $profile;

$collection->insert($data);
$m = new Mongo();
$db = $m->xhprof;
$collection = $db->results;
