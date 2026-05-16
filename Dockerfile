FROM php:8.2-apache

RUN docker-php-ext-install pdo_mysql \
    && a2enmod rewrite headers

# App lives at /chicken_ordering/ in the URL (same path as XAMPP).
WORKDIR /var/www/html

COPY . /var/www/html/chicken_ordering/

RUN chown -R www-data:www-data /var/www/html/chicken_ordering
