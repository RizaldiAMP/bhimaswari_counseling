#!/bin/bash
set -e

echo "Running storage:link..."
php artisan storage:link || true

echo "Optimizing application cache..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "Running database migrations..."
php artisan migrate --force

echo "Starting Apache..."
exec apache2-foreground
