FROM richarvey/nginx-php-fpm:latest

COPY . .

# Image config
ENV SKIP_COMPOSER 1
ENV WEBROOT /var/www/html/public
ENV PHP_ERRORS_STDERR 1
ENV RUN_SCRIPTS 1
ENV REAL_IP_HEADER 1

# Laravel config
ENV APP_ENV production
ENV APP_DEBUG true

# Allow composer to run as root
ENV COMPOSER_ALLOW_SUPERUSER 1

# Copy Nginx configuration file
COPY nginx-site.conf /etc/nginx/sites-available/default

CMD ["/start.sh"]
