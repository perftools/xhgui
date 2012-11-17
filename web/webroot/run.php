<?php
require dirname(__DIR__) . '/app/bootstrap.php';

$m = new Mongo();
$db = $m->xhprof;
$collection = $db->results;


$result = $collection->findOne(array('_id' => new MongoId($_GET['id'])));



$profile = $result['profile'];
$profile = exclusive($profile);
$e = print_r($profile, 1);
$f = print_r($result['profile'], 1);

function build_sorter($key) {
    return function ($a, $b) use ($key) {
        return strnatcmp($a[$key], $b[$key]);
    };
}

uasort($profile, build_sorter('wt'));


$template = load_template('runs/view.twig');
echo $template->display(array(
    'profile' => $profile,
    'result' => $result,
    'e' => $e,
    'f' => $f
));
