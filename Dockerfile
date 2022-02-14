# This dockerfile is optimized to have efficient layers caching.
# for example composer install is ran only if composer related files are updated.
# also modifying source, would not need to rebuild extensions layer.
# Author: Elan Ruusamäe <glen@pld-linux.org>

# build (build from source), prebuilt (use copy from last release image)
ARG BUILD_SOURCE=build

FROM alpine:3.15 AS alpine

FROM alpine AS base
ENV PHP_INI_DIR /etc/php7

# php-fpm runtime
FROM base AS php-build
RUN set -x \
	&& apk add --no-cache \
		nginx \
		php-cli \
		php-ctype \
		php-fpm \
		php-json \
		php-pdo \
		php-pdo_mysql \
		php-pdo_pgsql \
		php-pdo_sqlite \
		php-phar \
		php-session \
		php-simplexml \
		php7-pecl-mongodb \
	# Use www-data uid from alpine also present in docker php images
	&& adduser -u 82 -D -S -G www-data www-data \
	# Tweak php-fpm config
	&& sed -i \
		-e "s#^;daemonize\s*=\s*yes#daemonize = no#" \
		-e "s#^;error_log\s*=.*#error_log = /var/log/php/fpm.error.log#" \
		$PHP_INI_DIR/php-fpm.conf \
	&& POOL_CONFIG=$PHP_INI_DIR/php-fpm.d/www.conf \
	&& sed -i \
		-e "s#^listen\s*=.*#listen = [::]:9000#" \
		-e "s#^listen\.allowed_clients\s*=.*#;&#" \
		-e "s#^;access\.log\s*=.*#access.log = /var/log/php/fpm.access.log#" \
		-e "s#^;clear_env\s*=.*#clear_env = no#" \
		-e "s#^user = nobody\s*#user = www-data#" \
		-e "s#^group = nobody\s*#group = www-data#" \
		-e "s#^;catch_workers_output\s*=.*#catch_workers_output = yes#" \
		$POOL_CONFIG \
	&& rm -rf /var/log/php7 \
	&& ln -s php /var/log/php7 \
	&& install -d -o www-data -g www-data /var/log/php \
	&& ln -s php-fpm7 /usr/sbin/php-fpm \
	&& ln -s /dev/stderr /var/log/php/fpm.access.log \
	&& ln -s /dev/stderr /var/log/php/fpm.error.log \
	&& ln -s /dev/stdout /var/log/nginx/access.log \
	&& ln -s /dev/stderr /var/log/nginx/error.log \
	&& php -m

FROM xhgui/xhgui:latest AS php-prebuilt
# "php" alias
FROM php-$BUILD_SOURCE AS php

# prepare sources
FROM alpine AS source
WORKDIR /app
COPY . .
# mkdir "vendor" dir, so the next stage can optionally use external vendor dir contents
WORKDIR /app/vendor
RUN chmod -R a+rX /app

# install composer vendor
FROM php AS build
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
COPY --from=source /app/templates ./templates
COPY --from=source /app/src ./src
COPY --from=source /app/config ./config

# second run to invoke (possible) scripts and create autoloader
RUN composer install $COMPOSER_FLAGS --classmap-authoritative
# not needed runtime, cleanup
RUN rm -vf composer.* vendor/composer/*.json

# add vendor as separate docker layer
RUN mv vendor /

RUN install -d /cache -m 700

# runtime image from current build
FROM php AS runtime-build

ARG APPDIR=/var/www/xhgui
ARG WEBROOT=$APPDIR/webroot
WORKDIR $APPDIR

EXPOSE 80
CMD ["sh", "-c", "nginx && exec php-fpm"]
VOLUME "/run/nginx"

# runtime image from last release
FROM xhgui/xhgui:latest AS runtime-prebuilt
RUN rm -rf $(pwd)

# build final image
FROM runtime-$BUILD_SOURCE AS runtime
COPY --from=build /vendor ./vendor/
COPY --from=build /app ./
COPY --from=build --chown=www-data /cache ./cache/
