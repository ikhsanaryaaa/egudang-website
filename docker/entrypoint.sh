#!/bin/bash
set -e

echo "==> Starting E-Gudang application..."

# Generate application key jika belum ada
if [ -z "$APP_KEY" ]; then
    echo "==> Generating application key..."
    php artisan key:generate --force
fi

# Clear any stale route cache (Filament routes are dynamic, must not be cached)
echo "==> Clearing route cache..."
php artisan route:clear

# Cache konfigurasi untuk production
echo "==> Caching configuration..."
php artisan config:cache
php artisan view:cache

# Cache icons (Filament)
echo "==> Caching icons..."
php artisan icons:cache || echo "==> Icons cache skipped (not available)"

# Jalankan database migration
echo "==> Running database migrations..."
php artisan migrate --force

# Buat storage link
echo "==> Creating storage link..."
php artisan storage:link --force || echo "==> Storage link already exists"

# Fix permissions
echo "==> Fixing permissions..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

echo "==> Application ready!"

# Jalankan supervisor (atau command lain yang diberikan)
exec "$@"
