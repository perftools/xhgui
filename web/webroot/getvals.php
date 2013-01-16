<?php
header('Content-Type: application/json');
require dirname(__DIR__) . '/bootstrap.php';
$m = new Mongo();
$db = $m->xhprof;
$collection = $db->results;

$query = json_decode($_POST['query']);
$error = array();
if (is_null($query))
{
    $error['query'] = json_last_error();
}

$retrieve = json_decode($_POST['retrieve']);
if (is_null($retrieve))
{
    $error['retrieve'] = json_last_error();
}

if (count($error) > 0)
{
    echo json_encode(array('error' => $error));
    exit;
}

$perPage = Xhgui_Config::read('page.limit');
$res = $collection->find($query, $retrieve)
    ->limit($perPage);
$r = iterator_to_array($res);

echo json_encode($r);
