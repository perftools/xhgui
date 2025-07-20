#!/usr/bin/env php
<?php

use XHGui\Application;

require __DIR__ . '/../vendor/autoload.php';

$options = getopt('f:d:');

var_dump($options);
if (!isset($options['f']) && !isset($options['d'])) {
    throw new InvalidArgumentException('You should define either a file or a directory to be loaded');
}

$files = [];
if (isset($options['f'])) {
    $files[] = $options['f'];
}

if (isset($options['d'])) {
    $directory = $options['d'];
    if (!is_dir($directory)) {
        throw new InvalidArgumentException($directory . ' isn\'t a valid directory');
    }
    if (!is_readable($directory)) {
        throw new InvalidArgumentException($directory . ' isn\'t readable');
    }
    $dirFiles = glob($directory . '/*.jsonl');
    if ($dirFiles === false) {
        throw new RuntimeException('Failed to read directory: ' . $directory);
    }

    $files = array_merge($files, $dirFiles);
}

$app = new Application();
$saver = $app->getSaver();

foreach ($files as $file) {
    if (!is_readable($file)) {
        throw new InvalidArgumentException($file . ' isn\'t readable');
    }

    $fp = fopen($file, 'r');
    if (!$fp) {
        throw new RuntimeException('Can\'t open ' . $file);
    }
    echo "Processing file:" . $file . PHP_EOL;
    while (!feof($fp)) {
        $line = fgets($fp);
        $data = json_decode($line, true);
        if ($data) {
            try {
                $saver->save($data);
            } catch (Throwable $e) {
                error_log($e);
            }
        }
    }
    fclose($fp);
}
