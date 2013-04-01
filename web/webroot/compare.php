<?php
require dirname(__DIR__) . '/bootstrap.php';

$db = Xhgui_Db::connect();

$profiles = new Xhgui_Profiles($db->results);

$baseRun = $headRun = $candidates = null;
$pagination = null;

if (!empty($_GET['base'])) {
    $baseRun = $profiles->get($_GET['base']);
}

if ($baseRun && empty($_GET['head'])) {
    $pagination = array(
        'sort' => isset($_GET['sort']) ? $_GET['sort'] : null,
        'page' => isset($_GET['page']) ? $_GET['page'] : null,
        'perPage' => Xhgui_Config::read('page.limit'),
    );
    $candidates = $profiles->getForUrl(
        $baseRun->getMeta('simple_url'),
        $pagination
    );
}

if (!empty($_GET['head'])) {
    $headRun = $profiles->get($_GET['head']);
}

$template = Xhgui_Template::load('runs/compare.twig');
echo $template->display(array(
    'base_run' => $baseRun,
    'head_run' => $headRun,
    'candidates' => $candidates,
    'url_params' => $_GET,
    'date_format' => Xhgui_Config::read('date.format'),
));
