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

function runProcess($cmd, $input = null) {
    $descriptorSpec = array(
        0 => array('pipe', 'r'),
        1 => array('pipe', 'w'),
        2 => array('pipe', 'w')
    );
    $process = proc_open(
        $cmd,
        $descriptorSpec,
        $pipes
    );
    if (!is_resource($process)) {
        return 'ERROR - Could not start subprocess.';
    }
    $output = $error = '';
    fwrite($pipes[0], $input);
    fclose($pipes[0]);

    $output = stream_get_contents($pipes[1]);
    fclose($pipes[1]);

    $error = stream_get_contents($pipes[2]);
    fclose($pipes[2]);
    proc_close($process);
    if (strlen($error)) {
        return 'ERROR - ' . $error;
    }
    return $output;
}

/**
 * Composer setup.
 */
if (!file_exists(__DIR__ . '/composer.phar')) {
    out("Downloading composer.");
    $cmd = "php -r \"eval('?>'.file_get_contents('https://getcomposer.org/installer'));\"";
    $output = runProcess($cmd);
    out($output);
} else {
    out("Composer already installed.");
}

if (!file_exists(__DIR__ . '/composer.phar')) {
    out('ERROR - No composer found');
    out('download failed, possible reasons:');
    out(' - you\'re behind a proxy.');
    out(' - composer servers is not available at the moment.');
    out(' - something wrong with network configuration.');
    out('please try download it manually from https://getcomposer.org/installer and follow manual.');
    out('');
    exit(9);
}

out("Installing dependencies.");
$cmd = 'php ' . __DIR__ . '/composer.phar update --prefer-dist';
$output = runProcess($cmd);
out($output);


/**
 * File permissions.
 */
out('Checking permissions for cache directory.');
$worldWritable = bindec('0000000111');

// Get current permissions in decimal format so we can bitmask it.
$currentPerms = octdec(substr(sprintf('%o', fileperms('./cache')), -4));

if (($currentPerms & $worldWritable) != $worldWritable) {
	out('Attempting to set permissions on cache/');
	$result = chmod(__DIR__ . '/cache', $currentPerms | $worldWritable);
	if ($result) {
		out('Permissions set on cache/');
	} else {
		out('Failed to set permissions on cache/ you must do it yourself.');
	}
} else {
	out('Permissions on cache/ are ok.');
}
