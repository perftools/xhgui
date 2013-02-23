<?php
require dirname(__DIR__) . '/bootstrap.php';

$id = $_GET['id'];
$symbol = $_GET['symbol'];

$db = new Xhgui_Db();

$result = $db->get($id);
list($parent, $current, $children) = Xhgui_Profile::getRelatives($result['profile'], $symbol);

$template = load_template('runs/symbol-view.twig');
echo $template->display(array(
    'symbol' => $symbol,
    'id' => $id,
    'parent' => $parent,
    'current' => $current,
    'children' => $children,
));
