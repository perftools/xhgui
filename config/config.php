<?php
/**
 * Configuration for Xhgui
 */
return array(
    'debug' => false,
    'mode' => 'development',
    'db.host' => 'mongodb://localhost:27017',
    'db.db' => 'xhprof',
    // Allows you to pass additional options like replicaSet to MongoClient.
    'db.options' => array(),
    'templates.path' => XHGUI_ROOT_DIR . '/src/templates',
    'date.format' => 'M jS H:i:s',
    'detail.count' => 6,
    'page.limit' => 25
);
