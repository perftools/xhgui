#!/bin/bash
pecl install mongodb || true
echo "extension = mongodb.so" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini

git clone https://github.com/tideways/php-profiler-extension.git
cd php-profiler-extension

if [[ "$TRAVIS_PHP_VERSION" == "5.5" ]] || [[ "$TRAVIS_PHP_VERSION" == "5.6" ]]; then
    git checkout 4.x
    echo "extension=tideways.so" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini
    echo "tideways.auto_prepend_library=0" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini
else
    git checkout master
    echo "extension=tideways_xhprof.so" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini
fi

phpize && ./configure && make && make install && cd ..

phpenv rehash
composer install --prefer-dist --dev
chmod -R 0777 cache/
