FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip

RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd opcache

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/zz-opcache.ini

WORKDIR /var/www
COPY . .

RUN composer install --no-scripts --no-autoloader

EXPOSE 9000
CMD ["php-fpm"]
