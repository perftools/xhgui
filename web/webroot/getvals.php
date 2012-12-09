<?php
require dirname(__DIR__) . '/app/bootstrap.php';
$m = new Mongo();
$db = $m->xhprof;
$collection = $db->results;

$query = json_decode($_POST['query']);
$retrieve = json_decode($_POST['retrieve']);

$res = $collection->find($query, $retrieve)->limit(DISPLAY_LIMIT);
$r = iterator_to_array($res);
header('Content-Type: application/json');
echo json_encode($r);