<?php
/**
 * Error handlers.
 */
$app->error(function (Exception $e) use ($app) {
    $app->render('error/view.twig', array(
        'message' => $e->getMessage(),
        'stack_trace' => $e->getTraceAsString(),
    ));
});
