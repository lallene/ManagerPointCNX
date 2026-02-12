FROM php:8.0-fpm

# Installation des dépendances pour Laravel & Excel
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev zip libzip-dev unzip git curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# On définit le répertoire de travail sur le sous-dossier application
WORKDIR /var/www/application