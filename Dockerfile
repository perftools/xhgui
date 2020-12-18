# This dockerfile is optimized to have efficient layers caching.
# for example composer install is ran only if composer related files are updated.
# also modifying source, would not need to rebuild extensions layer.
# Author: Elan Ruusamäe <glen@pld-linux.org>

FROM alpine:3.12 AS base

ENV PHP_INI_DIR /etc/php7

# ext-mongodb: build and stage
FROM base AS build-ext-mongodb
RUN apk add --no-cache alpine-sdk openssl-dev php7-dev php7-openssl php7-pear
RUN pecl install mongodb

FROM base AS stage-ext-mongodb
RUN apk add binutils

WORKDIR /build/etc/php7/conf.d
RUN echo extension=mongodb.so > mongodb.ini

WORKDIR /build/usr/lib/php7/modules
COPY --from=build-ext-mongodb /usr/lib/php7/modules/mongodb.so .
RUN strip *.so && chmod a+rx *.so

# php-fpm runtime
FROM base AS php
RUN set -x \
	&& apk add --no-cache \
		php-cli \
		php-ctype \
		php-fpm \
		php-json \
		php-pdo \
		php-session \
		php-pdo_mysql \
		php-pdo_pgsql \
		php-pdo_sqlite \
	&& ln -s /usr/sbin/php-fpm7 /usr/sbin/php-fpm \
	# Use www-data uid/gid from alpine also present in docker php images
	&& addgroup -g 82 -S www-data \
	&& adduser -u 82 -D -S -G www-data www-data \
	# Tweak php-fpm config
	&& sed -i \
		-e "s#^;daemonize\s*=\s*yes#daemonize = no#" \
		-e "s#^error_log\s*=.*#error_log = /var/log/php/fpm.error.log#" \
		$PHP_INI_DIR/php-fpm.conf \
	&& POOL_CONFIG=$PHP_INI_DIR/php-fpm.d/www.conf \
	&& sed -i \
		-e "s#^listen\s*=.*#listen = [::]:9000#" \
		-e "s#^listen\.allowed_clients\s*=.*#;&#" \
		-e "s#^;access\.log\s*=.*#access.log = /var/log/php/fpm.access.log#" \
		-e "s#^;clear_env\s*=.*#clear_env = no#" \
		-e "s#^user = nobody\s*=.*#user = www-data#" \
		-e "s#^group = nobody\s*=.*#group = www-data#" \
		-e "s#^;catch_workers_output\s*=.*#catch_workers_output = yes#" \
		$POOL_CONFIG \
	&& install -d -o www-data -g www-data /var/log/php \
	&& ln -sf /proc/self/fd/2 /var/log/php/fpm.access.log \
	&& ln -sf /proc/self/fd/2 /var/log/php/fpm.error.log \
	&& php -m

# prepare sources
FROM scratch AS source
WORKDIR /app
COPY . .
# mkdir "vendor" dir, so the next stage can optionally use external vendor dir contents
WORKDIR /app/vendor

# install composer vendor
FROM php AS build
# extra deps for composer
RUN apk add --no-cache \
		php-phar \
	&& php -m
WORKDIR /app
ARG COMPOSER_FLAGS="--no-interaction --no-suggest --ansi --no-dev"
COPY --from=composer:1.10 /usr/bin/composer /usr/bin/

COPY --from=source /app/composer.* ./
COPY --from=source /app/vendor ./vendor

# install in two steps to cache composer run based on composer.* files
RUN composer install $COMPOSER_FLAGS --no-scripts --no-autoloader

# copy rest of the project. copy in order that is least to most changed
COPY --from=source /app/webroot ./webroot
COPY --from=source /app/external ./external
COPY --from=source /app/src ./src
COPY --from=source /app/config ./config

# second run to invoke (possible) scripts and create autoloader
RUN composer install $COMPOSER_FLAGS --classmap-authoritative
# not needed runtime, cleanup
RUN rm -vf composer.* vendor/composer/*.json

# add vendor as separate docker layer
RUN mv vendor /

RUN install -d /cache -m 700

# build runtime image
FROM php
ARG APPDIR=/var/www/xhgui
ARG WEBROOT=$APPDIR/webroot
WORKDIR $APPDIR

EXPOSE 9000
CMD ["php-fpm", "-F"]

COPY --from=stage-ext-mongodb /build /
COPY --from=build --chown=www-data /cache ./cache/
COPY --from=build /vendor ./vendor/
COPY --from=build /app ./
