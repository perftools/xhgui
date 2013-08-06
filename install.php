<?php
/**
 * Basic install script for XHGui2.
 *
 * Does the following things.
 *
 * - Downloads composer.
 * - Installs dependencies.
 */
echo "Downloading composer:\n";
exec("php -r \"eval('?>'.file_get_contents('https://getcomposer.org/installer'));\"");

echo "Installing dependencies:\n";
exec('php composer.phar install');
