FROM php:8.4-fpm

WORKDIR "/application"

RUN apt-get update

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN apt-get install libzip-dev zip libicu-dev libpq-dev -y

RUN apt-get update && apt-get install -y nano

RUN docker-php-ext-install zip && docker-php-ext-configure intl && docker-php-ext-install intl

RUN docker-php-ext-configure pgsql --with-pgsql=/usr/local/pgsql && docker-php-ext-install pdo_pgsql pgsql

RUN apt-get update && \
    apt-get install -y libfreetype6-dev libjpeg62-turbo-dev libpng-dev && \
    docker-php-ext-configure gd --with-freetype=/usr/include/ --with-jpeg=/usr/include/ && \
    docker-php-ext-install gd
