<?php
ini_set('display_errors', 1);
if (!defined('XHGUI_ROOT_DIR')) {
    require dirname(__DIR__).'/src/bootstrap.php';
}


$di = new Xhgui_ServiceContainer();

$sites = $di['sites'];
$sites
    ->setValidate(false)
    ->setCurrent($_SERVER['PHP_AUTH_USER']);
$collection = $di['profiles']->getCollection();

if (false === array_key_exists('archive', $_FILES)) {
    header('HTTP/1.1 400 No archive uploaded');
    exit;
}

$archive = $_FILES['archive'];
if (false === array_key_exists('type', $archive)) {
    header('HTTP/1.1 400 Invalid archive type');
    exit;
} elseif ('application/zip' !== $archive['type']) {
    header('HTTP/1.1 400 Invalid archive type');
    exit;
} elseif ('application/zip' !== mime_content_type($archive['tmp_name'])) {
    header('HTTP/1.1 400 Invalid archive type');
    exit;
}

$archive = zip_open($archive['tmp_name']);
while (false !== ($entry = zip_read($archive))) {
    // entry by entry
    if (zip_entry_open($archive, $entry, 'rb')) {
        $content = '';
        while ($data = zip_entry_read($entry)) {
            $content .= $data;
        }
        zip_entry_close($entry);

        if ($content) {
            $payload = json_decode($content, true);
            if ($payload) {
                $time = $payload['meta']['request_time'];
                unset($payload['meta']['request_time']);

                if (false !== strpos($time, '.')) {
                    $time = explode('.', $time);
                } else {
                    $time = [$time, 0];
                }
                $payload['meta'] = array_merge(
                    $payload['meta'],
                    [
                        'request_ts' => new MongoDate($time[0]),
                        'request_ts_micro' => new MongoDate($time[0], $time[1]),
                        'request_date' => date('Y-m-d', $time[0]),
                    ]
                );
                $collection->save($payload);
            }
        }
    }
}
zip_close($archive);
