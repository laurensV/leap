FROM php:7.1-fpm

RUN docker-php-ext-install pdo_mysql \
    && docker-php-ext-install json

RUN curl -sS https://getcomposer.org/installer | \
    php -- --install-dir=/usr/bin/ --filename=composer
