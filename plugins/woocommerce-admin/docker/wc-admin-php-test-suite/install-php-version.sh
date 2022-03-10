#!/bin/bash

echo "Installing packages for PHP$PHP_VERSION"

apk --no-cache add \
    php$PHP_VERSION \
    php$PHP_VERSION-bcmath \
    php$PHP_VERSION-ctype \
    php$PHP_VERSION-curl \
    php$PHP_VERSION-dom \
    php$PHP_VERSION-exif \
    php$PHP_VERSION-json \
    php$PHP_VERSION-opcache \
    php$PHP_VERSION-openssl \
    php$PHP_VERSION-mbstring \
    php$PHP_VERSION-mysqli \
    php$PHP_VERSION-pcntl \
    php$PHP_VERSION-pdo \
    php$PHP_VERSION-pdo_pgsql \
    php$PHP_VERSION-pdo_sqlite \
    php$PHP_VERSION-pdo_mysql \
    php$PHP_VERSION-phar \
    php$PHP_VERSION-session \
    php$PHP_VERSION-soap \
    php$PHP_VERSION-tokenizer \
    php$PHP_VERSION-xml \
    php$PHP_VERSION-xmlreader \
    php$PHP_VERSION-zip \
    php$PHP_VERSION-zlib    
if [ "$PHP_VERSION" == "8" ]; then
    apk --no-cache add php8-pecl-mcrypt php8-pecl-xdebug
    ln -s /usr/bin/php8 /usr/bin/php
else
    apk --no-cache add php7-mcrypt php7-xdebug
fi
