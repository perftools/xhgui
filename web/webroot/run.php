<?php
require dirname(__DIR__) . '/app/bootstrap.php';

$m = new Mongo();
$db = $m->xhprof;
$collection = $db->results;


$result = $collection->findOne(array('_id' => new MongoId($_GET['id'])));



$profile = $result['profile'];

function build_sorter($key) {
    return function ($a, $b) use ($key) {
        return strnatcmp($a[$key], $b[$key]);
    };
}

uasort($profile, build_sorter('wt'));


$template = load_template('runs/view.twig');
echo $template->display(array(
    'profile' => $profile,
    'result' => $result
));
