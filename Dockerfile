# Use the official PHP image as the base image
FROM php:8.1-apache

# Install required PHP extensions and any additional dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    && docker-php-ext-install mysqli pdo pdo_mysql gd zip mbstring xml

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set the working directory
WORKDIR /var/www/html

# Copy the composer.json and composer.lock files and install dependencies
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-ansi

# Copy the project files into the container
COPY . .

# Enable mod_rewrite for Apache
RUN a2enmod rewrite

# Change the ownership of the project files
RUN chown -R www-data:www-data /var/www/html