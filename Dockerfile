FROM php:8.3-fpm-alpine

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN apk add --no-cache icu-dev libzip-dev git unzip \
    && docker-php-ext-install -j$(nproc) intl pdo_mysql opcache

WORKDIR /var/www/html
