#!/bin/bash -e

if [[ "$TRAVIS_PHP_VERSION" == "7.2" ]]; then
	# installed, but not enabled?
	echo "extension = mongodb.so" >> ~/.phpenv/versions/$(phpenv version-name)/etc/conf.d/travis.ini
else
    php -m | grep -q mongodb || pecl install -f mongodb
fi

composer install --prefer-dist
chmod -R 0777 cache/
