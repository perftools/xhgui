<?php
require dirname(__DIR__) . '/bootstrap.php';

$db = Xhgui_Db::connect();
$watches = new Xhgui_WatchFunctions($db->watches);
$saved = false;

if (!empty($_POST['watch'])) {
	foreach ($_POST['watch'] as $data) {
		$watches->save($data);
	}
	$saved = true;
}

$watched = $watches->getAll();

$template = Xhgui_Template::load('watch/list.twig');
echo $template->render(array(
	'watched' => $watched,
	'saved' => $saved
));
