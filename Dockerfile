FROM alpine:3.7

# Add alpine repositories reqiored for PHP MongoDB extension.
RUN echo "@testing http://nl.alpinelinux.org/alpine/edge/testing"  >> /etc/apk/repositories

# Add required packages
RUN apk add --no-cache mongodb nginx runit git curl php7 php7-session php7-fpm php7-zip \
        php7-mongodb@testing php7-mbstring php7-json php7-sockets php7-openssl php7-curl \
        php7-phar php7-zlib php7-dom php7-ctype php7-tokenizer

# set up config files
COPY docker/ /etc

# Add the code
ADD ./ /var/www/xhgui/

WORKDIR /var/www/xhgui

RUN mkdir -p /data/db && chmod -R 777 /data/db && \
    mkdir -p /run/nginx && chmod -R 0700 /run/nginx && chown -R nginx:nginx /run/nginx

# Install dependencies.
RUN curl -LO https://getcomposer.org/composer.phar && \
    php composer.phar config "platform.ext-mongo" "1.6.16" && \
    php install.php

RUN chown -R nginx:nginx /var/www/xhgui

CMD ["runsvdir", "-P", "/etc/service"]

# Expose nginx port, so the frontend is accessible.
EXPOSE 80
