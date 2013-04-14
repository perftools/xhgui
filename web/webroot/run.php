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

// Watched Functions Block
$watches = new Xhgui_WatchFunctions($db->watches);
$watchedFunctions = array();
foreach ($watches->getAll() as $watch) {
    $matches = $result->getWatched($watch['name']);
    if ($matches) {
        $watchedFunctions = array_merge($watchedFunctions, $matches);
    }
}

$profile = $result->sort('ewt', $result->getProfile());

$template = Xhgui_Template::load('runs/view.twig');
echo $template->display(array(
    'profile' => $profile,
    'result' => $result,
    'wall_time' => $timeChart,
    'memory' => $memoryChart,
    'watches' => $watchedFunctions,
    'date_format' => Xhgui_Config::read('date_format'),
));
