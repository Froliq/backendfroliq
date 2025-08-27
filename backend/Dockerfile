# Use PHP with Apache
FROM php:8.1-apache

# Install required PHP extensions
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy backend files
COPY backend/ /var/www/html/

# Set working directory
WORKDIR /var/www/html/

# Expose port
EXPOSE 80
