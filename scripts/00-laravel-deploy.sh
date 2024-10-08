#!/usr/bin/env bash

echo "Running composer"
cp /etc/secrets/.env .env

composer install --no-dev --working-dir=/var/www/html

echo "Clearing caches..."
php artisan optimize:clear

echo "Caching config..."
php artisan config:cache

echo "Caching routes..."
php artisan route:cache

echo "Running migrations..."
php artisan migrate --force

echo "done deploying"

# Start PHP-FPM
service php8.2-fpm start

# Start Nginx
nginx -g "daemon off;"
