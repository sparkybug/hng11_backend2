FROM php:8.2-fpm

# Install Nginx
RUN apt-get update && apt-get install -y nginx

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application files
COPY . /var/www/html

# Set working directory
WORKDIR /var/www/html

# Copy Nginx configuration file
COPY nginx-site.conf /etc/nginx/sites-available/default

# Ensure permissions are correct
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Start PHP-FPM and Nginx
CMD ["sh", "-c", "php-fpm & nginx -g 'daemon off;'"]
