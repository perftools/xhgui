# This dockerfile is optimized to have efficient layers caching.
# for example composer install is ran only if composer related files are updated.
# also modifying source, would not need to rebuild extensions layer.
# Author: Elan Ruusamäe <glen@pld-linux.org>

FROM alpine:3.21 AS alpine

FROM alpine AS base
ENV PHP_SUFFIX=83
ENV PHP_INI_DIR=/etc/php$PHP_SUFFIX

# php-fpm runtime
FROM base AS php
RUN set -x \
	&& apk add --no-cache \
		nginx \
		php$PHP_SUFFIX-cli \
		php$PHP_SUFFIX-ctype \
		php$PHP_SUFFIX-fpm \
		php$PHP_SUFFIX-iconv \
		php$PHP_SUFFIX-json \
		php$PHP_SUFFIX-pdo \
		php$PHP_SUFFIX-pdo_mysql \
		php$PHP_SUFFIX-pdo_pgsql \
		php$PHP_SUFFIX-pdo_sqlite \
		php$PHP_SUFFIX-pecl-mongodb \
		php$PHP_SUFFIX-phar \
		php$PHP_SUFFIX-session \
		php$PHP_SUFFIX-simplexml \
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
	&& rm -rf /var/log/php$PHP_SUFFIX \
	&& ln -s php /var/log/php$PHP_SUFFIX \
	&& install -d -o www-data -g www-data /var/log/php \
	&& ln -s php-fpm$PHP_SUFFIX /usr/sbin/php-fpm \
	&& ln -s /dev/stderr /var/log/php/fpm.access.log \
	&& ln -s /dev/stderr /var/log/php/fpm.error.log \
	&& ln -s /dev/stdout /var/log/nginx/access.log \
	&& ln -s /dev/stderr /var/log/nginx/error.log \
	&& ln -sf php$PHP_SUFFIX /usr/bin/php \
	&& php -m

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
FROM php AS runtime

ARG APPDIR=/var/www/xhgui
ARG WEBROOT=$APPDIR/webroot
WORKDIR $APPDIR

EXPOSE 80
CMD ["sh", "-c", "nginx -g 'pid /dev/shm/nginx.pid;' && exec php-fpm"]

COPY --from=build /vendor ./vendor/
COPY --from=build /app ./
COPY --from=build --chown=www-data /cache ./cache/
