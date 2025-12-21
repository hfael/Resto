FROM php:8.3-apache

RUN apt-get update && apt-get install -y \
    libonig-dev \
    libzip-dev \
    zip \
    unzip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    git \
    curl \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install gd mbstring zip pdo_mysql mysqli

RUN a2enmod rewrite

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

WORKDIR /var/www/html
