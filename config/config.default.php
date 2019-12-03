<?php
/**
 * Default configuration for Xhgui
 */

$mongoUri = getenv('XHGUI_MONGO_URI') ?: '127.0.0.1:27017';
$mongoUri = str_replace('mongodb://', '', $mongoUri);
$mongoDb = getenv('XHGUI_MONGO_DB') ?: 'xhprof';

return array(
    'debug' => false,
    'mode' => 'development',

    // Can be mongodb, file or upload.

    // For file
    //
    //'save.handler' => 'file',
    //'save.handler.filename' => dirname(__DIR__) . '/cache/' . 'xhgui.data.' . microtime(true) . '_' . substr(md5($url), 0, 6),

    // For upload
    //
    // Saving profile data by upload is only recommended with HTTPS
    // endpoints that have IP whitelists applied.
    //
    // The timeout option is in seconds and defaults to 3 if unspecified.
    //
    //'save.handler' => 'upload',
    //'save.handler.upload.uri' => 'https://example.com/run/import',
    //'save.handler.upload.timeout' => 3,

    // For MongoDB
    'save.handler' => 'mongodb',
    'db.host' => sprintf('mongodb://%s', $mongoUri),
    'db.db' => $mongoDb,

    'pdo' => array(
        'dsn' => 'sqlite:/tmp/xhgui.sqlite3',
        'user' => null,
        'pass' => null,
        'table' => 'results'
    ),

    // Allows you to pass additional options like replicaSet to MongoClient.
    // 'username', 'password' and 'db' (where the user is added)
    'db.options' => array(),
    'templates.path' => dirname(__DIR__) . '/src/templates',
    'date.format' => 'M jS H:i:s',
    'detail.count' => 6,
    'page.limit' => 25,

    // call fastcgi_finish_request() in shutdown handler
    'fastcgi_finish_request' => true,

    // Profile x in 100 requests. (E.g. set XHGUI_PROFLING_RATIO=50 to profile 50% of requests)
    // You can return true to profile every request.
    'profiler.enable' => function() {
        $ratio = getenv('XHGUI_PROFILING_RATIO') ?: 100;
        return (getenv('XHGUI_PROFILING') !== false) && (mt_rand(1, 100) <= $ratio);
    },

    'profiler.simple_url' => function($url) {
        return preg_replace('/\=\d+/', '', $url);
    },
    
    //'profiler.replace_url' => function($url) {
    //    return str_replace('token', '', $url);
    //},

    'profiler.options' => array(),

    'profiler.skip_built_in' => false,
);
