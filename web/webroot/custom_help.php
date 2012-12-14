<?php
require dirname(__DIR__) . '/app/bootstrap.php';
$template = load_template('runs/custom_help.twig');
$m = new Mongo();
$db = $m->xhprof;
$collection = $db->results;

$res = $collection->findOne();
    
    
echo $template->render(array(
    'data' => print_r($res, 1)
));