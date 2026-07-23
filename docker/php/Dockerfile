FROM php:8.4-fpm-alpine

WORKDIR /var/www/html

RUN apk add --no-cache \
    libzip-dev zip \
    freetype-dev libpng-dev libwebp-dev jpeg-dev libjpeg-turbo-dev \
    autoconf build-base \
    libintl icu-dev \
    nodejs npm

RUN docker-php-ext-configure zip
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp
RUN docker-php-ext-install -j$(nproc) gd zip pdo_mysql exif pcntl bcmath
RUN docker-php-ext-configure intl && docker-php-ext-install intl
RUN pecl install redis && docker-php-ext-enable redis

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY --chown=www-data:www-data . /var/www/html

RUN mkdir -p /.config/psysh && chmod -R 777 /.config && \
    mkdir -p /.composer/cache/files && chmod -R 777 /.composer/cache/files
