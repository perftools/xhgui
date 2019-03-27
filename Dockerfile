# This dockerfile is optimized to have efficient layers caching.
# for example composer install is ran only if composer related files are updated.
# also modifying source, would not need to rebuild extensions layer.
# Author: Elan Ruusamäe <glen@pld-linux.org>

FROM php:7.3-fpm-alpine AS base

RUN set -x \
    && apk add --no-cache --virtual .build-deps ${PHPIZE_DEPS} \
	&& pecl install mongodb && docker-php-ext-enable mongodb \
    && apk del .build-deps

# prepare sources
FROM scratch AS source
WORKDIR /app
COPY . .
# mkdir "vendor" dir, so the next stage can use external vendor optionally
WORKDIR /app/vendor

# install composer vendor
FROM base AS build
WORKDIR /app
ARG COMPOSER_FLAGS="--no-interaction --no-suggest --ansi --no-dev"
COPY --from=composer:1.8 /usr/bin/composer /usr/bin/

COPY --from=source /app/composer.* ./
COPY --from=source /app/vendor ./vendor

# install in two steps to cache composer run based on composer.* files
RUN composer require --update-no-dev --no-scripts alcaeus/mongo-php-adapter ^1.1
RUN composer install $COMPOSER_FLAGS --no-scripts --no-autoloader

# copy rest of the project. copy in order that is least to most changed
COPY --from=source /app/webroot ./webroot
COPY --from=source /app/src ./src
COPY --from=source /app/config ./config

# second run to invoke (possible) scripts and create autoloader
RUN composer install $COMPOSER_FLAGS --classmap-authoritative
# not needed runtime, cleanup
RUN rm -vf composer.* vendor/composer/*.json

# build runtime image
FROM base
#ARG APPDIR=/app
ARG APPDIR=/var/www/xhgui
ARG WEBROOT=$APPDIR/webroot
WORKDIR $APPDIR

RUN mkdir -p cache && chmod -R 777 cache
COPY --from=build /app $APPDIR/
