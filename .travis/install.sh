#!/bin/bash -e

if [[ "$TRAVIS_PHP_VERSION" == "7.2" ]]; then
	# installed, but not enabled?
	echo "extension = mongodb.so" >> ~/.phpenv/versions/$(phpenv version-name)/etc/conf.d/travis.ini
else
	pecl install mongodb || true
fi

composer install --prefer-dist
chmod -R 0777 cache/
