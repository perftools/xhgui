# This dockerfile is optimized to have efficient layers caching.
# for example composer install is ran only if composer related files are updated.
# also modifying source, would not need to rebuild extensions layer.
# Author: Elan Ruusamäe <glen@pld-linux.org>

# Use alpine:edge for ext-mongodb:
# - https://gitlab.alpinelinux.org/alpine/aports/-/issues/12102
FROM alpine:edge AS base

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
		php7-pecl-mongodb \
	&& ln -s /usr/sbin/php-fpm7 /usr/sbin/php-fpm \
	# Use www-data uid/gid from alpine also present in docker php images
	&& addgroup -g 82 -S www-data \
	&& adduser -u 82 -D -S -G www-data www-data \
	&& php -m

# prepare sources
FROM scratch AS source
WORKDIR /app
COPY . .
# mkdir "vendor" dir, so the next stage can optionally use external vendor dir contents
WORKDIR /app/vendor

# install composer vendor
FROM base AS build
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
FROM base
ARG APPDIR=/var/www/xhgui
ARG WEBROOT=$APPDIR/webroot
WORKDIR $APPDIR

EXPOSE 9000
CMD ["php-fpm", "-F"]

COPY --from=build --chown=www-data /cache ./cache/
COPY --from=build /vendor ./vendor/
COPY --from=build /app ./
