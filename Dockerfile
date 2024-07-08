# Use the official PHP image with FPM
FROM php:8.2-fpm

# Install Nginx and other dependencies
RUN apt-get update && apt-get install -y nginx git unzip

# Set working directory
WORKDIR /var/www/html

# Copy application code
COPY . .

# Copy Nginx configuration file
COPY nginx-site.conf /etc/nginx/sites-available/default

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Ensure permissions are correct
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Expose port 80
EXPOSE 80

# Start Nginx and PHP-FPM
CMD ["sh", "-c", "service nginx start && php-fpm"]
