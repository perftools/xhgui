<?php
/**
 * Configuration for Xhgui
 */
return array(
    'debug' => false,
    'mode' => 'development',
    'save.handler' => 'mongodb', // mongodb, file
    //'save.handler.filename' => __DIR__.'/../data/xhgui_'.date('Ymd').'.dat', //needed for file save handler
    'db.host' => 'mongodb://localhost:27017',
    'db.db' => 'xhprof',
    // Allows you to pass additional options like replicaSet to MongoClient.
    'db.options' => array(),
    'templates.path' => XHGUI_ROOT_DIR . '/src/templates',
    'date.format' => 'M jS H:i:s',
    'detail.count' => 6,
    'page.limit' => 25
);
