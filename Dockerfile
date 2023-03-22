FROM php:8.1-fpm-alpine

# The following labels need to be set as part of the docker build process.
#   org.opencontainers.image.created
#   org.opencontainers.image.revision
LABEL org.opencontainers.image.url="https://laravel.com" \
    org.opencontainers.image.documentation="https://github.com/generationtux/php-healthz/blob/master/README.md" \
    org.opencontainers.image.source="https://github.com/generationtux/php-healthz/Dockerfile" \
    org.opencontainers.image.vendor="Generation Tux <engineering@generationtux.com>" \
    org.opencontainers.image.title="Laravel 6.x" \
    org.opencontainers.image.description="PHP built for use with the Laravel/Lumen framework" \
    com.generationtux.php.backend="fpm"

USER root

COPY ./docker/installComposer.sh /tmp/installComposer.sh

RUN apk --no-cache --update add bash ca-certificates libpq postgresql-dev curl git curl git mysql-client unzip wget zip postgresql-client \
    && apk add --no-cache --virtual build-dependencies autoconf build-base g++ make \
    && pecl install redis xdebug-3.1.4 \
    && docker-php-ext-install bcmath opcache pdo_mysql pdo_pgsql pcntl \
    && docker-php-ext-enable bcmath opcache redis xdebug \
    && chmod +x /tmp/installComposer.sh \
    && /tmp/installComposer.sh \
    && chown www-data:www-data /usr/local/bin/composer \
    && apk del --purge autoconf build-dependencies g++ make \
    && chown -R www-data:www-data /var/www

WORKDIR /var/www

USER www-data:www-data
