<?php

use XHGui\Saver\SaverInterface;
use XHGui\ServiceContainer;

if (!defined('XHGUI_ROOT_DIR')) {
    require dirname(__DIR__) . '/src/bootstrap.php';
}

$options = getopt('f:');

if (!isset($options['f'])) {
    throw new InvalidArgumentException('You should define a file to be loaded');
} else {
    $file = $options['f'];
}

if (!is_readable($file)) {
    throw new InvalidArgumentException($file.' isn\'t readable');
}

$fp = fopen($file, 'r');
if (!$fp) {
    throw new RuntimeException('Can\'t open '.$file);
}

$container = ServiceContainer::instance();
/** @var SaverInterface $saver */
$saver = $container['saver'];

while (!feof($fp)) {
    $line = fgets($fp);
    $data = json_decode($line, true);
    if ($data) {
        $saver->save($data);
    }
}
fclose($fp);
