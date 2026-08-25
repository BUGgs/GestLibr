FROM php:8.2-apache AS base

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        zip \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libonig-dev \
        libcurl4-openssl-dev \
        libxml2-dev \
        libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" mysqli pdo_mysql mbstring curl gd soap zip \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY docker/php/conf.d/legacy.ini /usr/local/etc/php/conf.d/legacy.ini
COPY docker/php/scripts/boot-dev.sh /usr/local/bin/boot-dev.sh
RUN chmod +x /usr/local/bin/boot-dev.sh

WORKDIR /var/www/html

FROM base AS prod

COPY web/ /var/www/html/
RUN composer install --working-dir=/var/www/html --no-dev --prefer-dist --optimize-autoloader --no-interaction \
    && chown -R www-data:www-data /var/www/html
