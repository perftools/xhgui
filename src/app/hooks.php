<?php

$app->hook('slim.before', function () use ($app) {
	$app->db = Xhgui_Db::connect();
});
