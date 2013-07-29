<?php
require dirname(__DIR__) . '/src/bootstrap.php';

$db = Xhgui_Db::connect();

$profiles = new Xhgui_Profiles($db->results);
$profile = $profiles->get($_GET['id']);

$template = Xhgui_Template::load('runs/callgraph.twig');
echo $template->display(array(
    'profile' => $profile,
    'date_format' => Xhgui_Config::read('date_format'),
    'callgraph' => $profile->getCallgraph(),
));
