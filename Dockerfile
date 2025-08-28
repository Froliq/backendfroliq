# Multi-service container: PHP 8.2 + Apache
FROM php:8.2-apache

# Install extensions
RUN docker-php-ext-install pdo pdo_mysql

# Enable useful Apache modules
RUN a2enmod rewrite headers

# Copy project into image
# Expecting build context to be the project root containing `frontend/` and `backend/`
COPY ./frontend/ /var/www/html/frontend/
COPY ./backend/  /var/www/html/backend/

# Make frontend the site root, backend accessible at /backend
RUN set -eux; \
    printf '%s\n' \
    '<VirtualHost *:80>' \
    '  ServerName localhost' \
    '  DocumentRoot /var/www/html/frontend' \
    '  <Directory /var/www/html/frontend>' \
    '    Options Indexes FollowSymLinks' \
    '    AllowOverride All' \
    '    Require all granted' \
    '  </Directory>' \
    '  Alias /backend /var/www/html/backend' \
    '  <Directory /var/www/html/backend>' \
    '    Options Indexes FollowSymLinks' \
    '    AllowOverride All' \
    '    Require all granted' \
    '  </Directory>' \
    '  # Basic permissive CORS for development' \
    '  Header always set Access-Control-Allow-Origin "*"' \
    '  Header always set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"' \
    '  Header always set Access-Control-Allow-Headers "Content-Type, Authorization"' \
    '</VirtualHost>' \
    > /etc/apache2/sites-available/000-default.conf

# Optional: .htaccess to route SPA paths back to index.html
RUN printf '%s\n' \
    'RewriteEngine On' \
    'RewriteCond %{REQUEST_FILENAME} !-f' \
    'RewriteCond %{REQUEST_FILENAME} !-d' \
    'RewriteRule ^ index.html [L]' \
    > /var/www/html/frontend/.htaccess

WORKDIR /var/www/html
EXPOSE 80
