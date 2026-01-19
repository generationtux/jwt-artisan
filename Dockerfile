ARG PHP_VERSION=8.4
FROM php:${PHP_VERSION}-fpm-alpine

LABEL org.opencontainers.image.url="https://laravel.com" \
    org.opencontainers.image.documentation="https://github.com/generationtux/jwt-artisan/blob/master/README.md" \
    org.opencontainers.image.source="https://github.com/generationtux/jwt-artisan/Dockerfile" \
    org.opencontainers.image.vendor="Generation Tux <engineering@generationtux.com>" \
    org.opencontainers.image.title="jwt-artisan" \
    org.opencontainers.image.description="JWT auth package for Laravel and Lumen"

USER root

RUN apk --no-cache --update add bash ca-certificates curl git unzip wget zip linux-headers \
    && apk add --no-cache --virtual build-dependencies autoconf build-base g++ make \
    && docker-php-ext-install bcmath opcache \
    && docker-php-ext-enable bcmath opcache \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && chown www-data:www-data /usr/local/bin/composer \
    && apk del --purge autoconf build-dependencies g++ make \
    && chown -R www-data:www-data /var/www

WORKDIR /var/www

USER www-data:www-data
