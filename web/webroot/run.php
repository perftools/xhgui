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
        if ($a[$key] == $b[$key])
        {
            return 0;
        }
        return $a[$key] > $b[$key] ? -1 : 1;
    };
}

uasort($profile, build_sorter('ewt'));

//Pie Chart Widget
$pie = array_slice($profile, 0, PIE_COUNT);

//Memory Block
$memory = $profile;
uasort($memory, build_sorter('emu'));


//Watched Functions Block
//The purpose of watched functions is to let developers call out functions whose performance they want to keep an eye on, they'll
//  always show up in the top left.
//Ideas: mysqli_query(), memcache_set(), etc. If those explode, it's an immediate red flag
$watches = array();
$watch_list = array(
    'stuff', 'strlen'
);
foreach($watch_list as $watchey)
{
    if (isset($profile[$watchey]))
    {
        $watches[$watchey] = $profile[$watchey];
    }
}

$template = load_template('runs/view.twig');
echo $template->display(array(
    'profile' => $profile,
    'result' => $result,
    'pie_data' => $pie,
    'memory' => $memory,
    'watches' => $watches
));
