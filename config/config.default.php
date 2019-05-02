<?php
/**
 * Default configuration for XHGui.
 *
 * To change these, create a called `config.php` file in the same directory,
 * and return an array from there with your overriding settings.
 */

return array(
    // Which backend to use for Xhgui_Saver.
    // Must be one of 'mongodb', or 'file'.
    //
    // Example (save to a temporary file):
    //
    //     'save.handler' => 'file',
    //     # Beware of file locking. You can adujst this file path
    //     # to reduce locking problems (eg uniqid, time ...)
    //     'save.handler.filename' => __DIR__.'/../data/xhgui_'.date('Ymd').'.dat',
    //
    'save.handler' => 'mongodb',

    'pdo' => array(
        'dsn' => null,
        'user' => null,
        'pass' => null,
        'table' => 'results'
    ),

    // Database options for MongoDB.
    //
    // - db.host: Connection string in the form `mongodb://[ip or host]:[port]`.
    //
    // - db.db: The database name.
    //
    // - db.options: Additional options for the MongoClient contructor,
    //               for example 'username', 'password', or 'replicaSet'.
    //               See <https://secure.php.net/mongoclient_construct#options>.
    //
    'db.host' => getenv('XHGUI_MONGO_HOST') ?: 'mongodb://127.0.0.1:27017',
    'db.db' => getenv('XHGUI_MONGO_DATABASE') ?: 'xhprof',
    'db.options' => array(),
    'run.view.filter.names' => array(
        'Zend*',
        'Composer*',
    ),

    // Whether to instrument a user request.
    //
    // NOTE: Only applies to using the external/header.php include.
    //
    // Must be a function that returns a boolean,
    // or any non-function value to disable the profiler.
    //
    // Default: Profile 1 in 100 requests.
    //
    // Example (profile all requests):
    //
    //     'profiler.enabled' => function() {
    //         return true;
    //     },
    //
    'profiler.enable' => function() {
        return rand(1, 100) === 42;
    },

    // Transformation for the "simple" variant of the URL.
    // This is stored as `meta.simple_url` and used for
    // aggregate data.
    //
    // NOTE: Only applies to using the external/header.php include.
    //
    // Must be a function that returns a string, or any
    // non-callable value for the default behaviour.
    //
    // Default: Remove numeric values after `=`. For example,
    // it turns "/foo?page=2" into "/foo?page".
    'profiler.simple_url' => null,

    // Enable to clean up the url saved to the DB
    // in this way is possible to remove sensitive data or other kind of data
    'profiler.replace_url' => null,

    // Additional options to be passed to the `_enable()` function
    // of the profiler extension (xhprof, tideways, etc.).
    //
    // NOTE: Only applies to using the external/header.php include.
    'profiler.options' => array(),

    // Date format used when browsing XHGui pages.
    //
    // Must be a format supported by the PHP date() function.
    // See <https://secure.php.net/date>.
    'date.format' => 'M jS H:i:s',

    // The number of items to show in "Top lists" with functions
    // using the most time or memory resources, on XHGui Run pages.
    'detail.count' => 6,

    // The number of items to show per page, on XHGui list pages.
    'page.limit' => 25,

);
