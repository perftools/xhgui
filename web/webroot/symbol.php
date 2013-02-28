<?php
require dirname(__DIR__) . '/bootstrap.php';

$id = $_GET['id'];
$symbol = $_GET['symbol'];

$db = Xhgui_Db::connect();
$profiles = new Xhgui_Profiles($db->results);

$result = $profiles->get($id);
list($parents, $current, $children) = Xhgui_Profile::getRelatives($result['profile'], $symbol);

$template = load_template('runs/symbol-view.twig');
echo $template->display(array(
    'symbol' => $symbol,
    'id' => $id,
    'parents' => $parents,
    'current' => $current,
    'children' => $children,
));
