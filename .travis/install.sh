#!/bin/bash -e

install_mongodb() {
    if [[ "$TRAVIS_PHP_VERSION" == "7.2" ]]; then
        # installed, but not enabled?
        echo "extension = mongodb.so" >> ~/.phpenv/versions/$(phpenv version-name)/etc/conf.d/travis.ini
    else
        php -m | grep -q mongodb || pecl install -f mongodb
    fi
}

install_mongodb

case "$XHGUI_SAVE_HANDLER:$XHGUI_PDO_DSN" in
pdo:mysql:*)
    mysql -uroot -e "create database xhgui"
    ;;
esac

composer install --prefer-dist
chmod -R 0777 cache/
