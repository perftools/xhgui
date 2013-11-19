<?php
/**
 * Default configuration for Xhgui
 */
return array(
    'debug' => false,
    'mode' => 'development',

    // Can be either mongodb or file.
    'save.handler' => 'mongodb',

    // Needed for file save handler. Beware of file locking. You can adujst this file path 
    // to reduce locking problems (eg uniqid, time ...)
    //'save.handler.filename' => __DIR__.'/../data/xhgui_'.date('Ymd').'.dat',
    'db.host' => 'mongodb://10.6.0.52:27017',
    'db.db' => 'xhprof',

    // Allows you to pass additional options like replicaSet to MongoClient.
    'db.options' => array(),
    'templates.path' => dirname(__DIR__) . '/src/templates',
    'date.format' => 'M jS H:i:s',
    'detail.count' => 6,
    'page.limit' => 25,

    // Profile 1 in 100 requests.
    'profiler.enable' => function () {
        return rand(100) === 42;
    }

);
