FROM php:8.2-fpm

WORKDIR /var/www/html

COPY . /var/www/html

RUN apt-get update && apt-get install -y \
    libzip-dev \
    && docker-php-ext-install zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN composer install

EXPOSE 9000
CMD ["php-fpm"]
