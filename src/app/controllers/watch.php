<?php
/**
 * Controller actions for watched functions.
 */
$app->get('/watch', function () use ($app) {
	$watches = new Xhgui_WatchFunctions($app->db->watches);
	$watched = $watches->getAll();

	$app->render('watch/list.twig', array(
		'watched' => $watched,
	));
})->name('watch.list');

$app->post('/watch', function () use ($app) {
	$watches = new Xhgui_WatchFunctions($app->db->watches);
	$saved = false;
	$request = $app->request();
	foreach ((array)$request->post('watch') as $data) {
		$saved = true;
		$watches->save($data);
	}
	if ($saved) {
		$app->flash('success', 'Watch functions updated.');
	}
	$app->redirect($app->urlFor('watch.list'));
})->name('watch.save');
