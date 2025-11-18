#!/bin/bash
set -e

echo "Starting Debt Manager application..."

# Create database file if it doesn't exist
if [ ! -f /var/www/database/database.sqlite ]; then
    echo "Creating SQLite database file..."
    touch /var/www/database/database.sqlite
    chown www-data:www-data /var/www/database/database.sqlite
    chmod 664 /var/www/database/database.sqlite
fi

# Run migrations
echo "Running database migrations..."
php artisan migrate --force

# Seed database if needed (optional, only on first run)
if [ ! -s /var/www/database/database.sqlite ]; then
    echo "Database is empty, running seeders..."
    php artisan db:seed --force || echo "No seeders to run or seeding failed, continuing..."
fi

# Clear and cache config for production
echo "Optimizing Laravel for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Application ready!"

# Start Apache
exec apache2-foreground
