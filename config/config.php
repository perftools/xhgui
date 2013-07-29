<?php
/**
 * Configuration for Xhgui
 */
return array(
    'debug' => true,
    'mode' => 'development',
    'db.host' => 'mongodb://localhost:27017',
    'db.db' => 'xhprof',
    'templates.path' => XHGUI_ROOT_DIR . '/src/templates',
    'date.format' => 'M jS H:i:s',
    'detail.count' => 6,
    'page.limit' => 25
);
