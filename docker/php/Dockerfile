FROM php:8.1-fpm-alpine

RUN apk add --no-cache $PHPIZE_DEPS bash wget zip unzip busybox-extras libzip-dev
RUN pecl install redis
RUN docker-php-ext-enable redis
RUN docker-php-ext-configure zip
RUN docker-php-ext-install zip
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

