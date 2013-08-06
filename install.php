<?php
/**
 * Basic install script for XHGui2.
 *
 * Does the following things.
 *
 * - Downloads composer.
 * - Installs dependencies.
 */
function out($out) {
    if (is_string($out)) {
        echo $out . "\n";
    }
    if (is_array($out)) {
        foreach ($out as $line) {
            out($line);
        }
    }
}

if (!file_exists('./composer.phar')) {
    out("Downloading composer.");
    exec("php -r \"eval('?>'.file_get_contents('https://getcomposer.org/installer'));\"", $output);
    out($output);
} else {
    out("Composer already installed.");
}

out("Installing dependencies.");
exec('php ./composer.phar update', $output);
out($output);
