FROM php:8.1-fpm-alpine

RUN apk add --no-cache $PHPIZE_DEPS bash wget git unzip busybox-extras rabbitmq-c-dev zip libzip-dev
RUN pecl install amqp redis
RUN docker-php-ext-enable amqp redis
RUN docker-php-ext-configure zip
RUN docker-php-ext-install pdo pdo_mysql mysqli bcmath sockets zip
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

