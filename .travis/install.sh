#!/bin/bash -e

if [[ "$TRAVIS_PHP_VERSION" == "7.2" ]]; then
	# installed, but not enabled?
	echo "extension = mongodb.so" >> ~/.phpenv/versions/$(phpenv version-name)/etc/conf.d/travis.ini
else
	pecl install mongodb || true
fi

git clone https://github.com/tideways/php-profiler-extension.git
cd php-profiler-extension

if [[ "$TRAVIS_PHP_VERSION" == "5.6" ]]; then
    git checkout 4.x
    echo "extension=tideways.so" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini
    echo "tideways.auto_prepend_library=0" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini
else
    git checkout master
    echo "extension=tideways_xhprof.so" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini
fi

phpize && ./configure && make && make install && cd ..
phpenv rehash

composer install --prefer-dist
chmod -R 0777 cache/
