<?php
require dirname(__DIR__) . '/bootstrap.php';

$db = Xhgui_Db::connect();

$template = Xhgui_Template::load('runs/compare.twig');
echo $template->display(array(
));
