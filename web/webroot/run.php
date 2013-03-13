<?php
require dirname(__DIR__) . '/bootstrap.php';

$db = Xhgui_Db::connect();
$detailCount = Xhgui_Config::read('detail.count');

$profiles = new Xhgui_Profiles($db->results);

$result = $profiles->get($_GET['id']);
$result->calculateExclusive();

// Exclusive wall time graph
$timeChart = $result->extractDimension('ewt', $detailCount);

// Memory Block
$memoryChart = $result->extractDimension('emu', $detailCount);

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

$profile = $result->sort('ewt', $result->getProfile());

$template = load_template('runs/view.twig');
echo $template->display(array(
    'profile' => $profile,
    'result' => $result,
    'wall_time' => $timeChart,
    'memory' => $memoryChart,
    'watches' => $watches
));
