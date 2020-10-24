# This dockerfile is optimized to have efficient layers caching.
# for example composer install is ran only if composer related files are updated.
# also modifying source, would not need to rebuild extensions layer.
# Author: Elan Ruusamäe <glen@pld-linux.org>

FROM php:7.3-fpm-alpine AS base

RUN set -x \
	&& apk add --no-cache --virtual .build-deps ${PHPIZE_DEPS} postgresql-dev openssl-dev \
	&& pecl install mongodb && docker-php-ext-enable mongodb \
	&& docker-php-ext-install pdo pdo_mysql pdo_pgsql \
	# https://github.com/docker-library/php/blob/c8c4d223a052220527c6d6f152b89587be0f5a7c/7.3/alpine3.12/fpm/Dockerfile#L166-L172
	&& runDeps=$( \
		scanelf --needed --nobanner --format '%n#p' --recursive /usr/local \
			| tr ',' '\n' \
			| sort -u \
			| awk 'system("[ -e /usr/local/lib/" $1 " ]") == 0 { next } { print "so:" $1 }' \
	) \
	&& apk add --no-cache $runDeps \
	&& apk del .build-deps

# prepare sources
FROM scratch AS source
WORKDIR /app
COPY . .
# mkdir "vendor" dir, so the next stage can optionally use external vendor dir contents
WORKDIR /app/vendor

# install composer vendor
FROM base AS build
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

# build runtime image
FROM base
ARG APPDIR=/var/www/xhgui
ARG WEBROOT=$APPDIR/webroot
WORKDIR $APPDIR

RUN mkdir -p cache && chmod -R 777 cache
COPY --from=build /vendor ./vendor/
COPY --from=build /app ./
