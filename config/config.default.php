<?php
/**
 * Default configuration for XHGui.
 *
 * To change these, create a file called `config.php` file in the same directory
 * and return an array from there with your overriding settings.
 */

return [
    // Which backend to use for Xhgui_Saver.
    // Must be one of 'mongodb', or 'pdo'.
    'save.handler' => getenv('XHGUI_SAVE_HANDLER') ?: 'mongodb',

    'pdo' => [
        'dsn' => getenv('XHGUI_PDO_DSN') ?: null,
        'user' => getenv('XHGUI_PDO_USER') ?: null,
        'pass' => getenv('XHGUI_PDO_PASS') ?: null,
        'table' => getenv('XHGUI_PDO_TABLE') ?: 'results'
    ],

    // If defined, add imports via upload (/run/import) must pass token parameter with this value
    'upload.token' => getenv('XHGUI_UPLOAD_TOKEN') ?: '',

    // Add this path prefix to all links and resources
    // If this is not defined, auto-detection will try to find it itself
    'path.prefix' => null,

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
    'db.options' => [],
    'db.driverOptions' => [],

    'run.view.filter.names' => [
        'Zend*',
        'Composer*',
    ],

    // Setup timezone for date formatting
    // Example: 'UTC', 'Europe/Tallinn'
    // If left empty, php default will be used (php.ini or compiled in default)
    'timezone' => '',

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
];
