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

/**
 * Composer setup.
 */
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


/**
 * File permissions.
 */
out('Checking permissions for cache directory.');
$worldWritable = bindec('0110000000');

// Get current permissions in decimal format so we can bitmask it.
$currentPerms = octdec(substr(sprintf('%o', fileperms('./cache')), -4));

if (($currentPerms & $worldWritable) != $worldWritable) {
	out('Attempting to set permissions on cache/');
	$result = chmod('./cache', $currentPerms | $worldWritable);
	if ($result) {
		out('Permissions set on cache/');
	} else {
		out('Failed to set permissions on cache/ you must do it yourself.');
	}
} else {
	out('Permissions on cache/ are ok.');
}
