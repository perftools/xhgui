<?php
require dirname(__DIR__) . '/bootstrap.php';

$id = $_GET['id'];
$symbol = $_GET['symbol'];

$db = Xhgui_Db::connect();
$profiles = new Xhgui_Profiles($db->results);

$profile = $profiles->get($id);
$profile->calculateExclusive();
list($parents, $current, $children) = $profile->getRelatives($symbol);

$template = Xhgui_Template::load('runs/symbol-view.twig');
echo $template->display(array(
    'symbol' => $symbol,
    'id' => $id,
    'parents' => $parents,
    'current' => $current,
    'children' => $children,
));
