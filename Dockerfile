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

# Enable mod_rewrite for Apache
RUN a2enmod rewrite

# Copy the project files into the container
COPY . /var/www/html/

# Set the working directory
WORKDIR /var/www/html

# Change the ownership of the project files
RUN chown -R www-data:www-data /var/www/html
