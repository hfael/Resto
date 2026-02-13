FROM php:8.3-apache

RUN apt-get update && apt-get install -y \
    libonig-dev libzip-dev zip unzip \
    libpng-dev libjpeg-dev libfreetype6-dev \
    git curl \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install gd mbstring zip pdo_mysql mysqli

RUN a2enmod rewrite

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

RUN composer install

# Apache doit servir /public et non /html
RUN sed -ri -e 's!/var/www/html!/var/www/public!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!/var/www/public!g' /etc/apache2/apache2.conf

WORKDIR /var/www/public
