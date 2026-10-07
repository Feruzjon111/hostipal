#!/usr/bin/env bash
set -euo pipefail
umask 0007

project_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$project_dir/backend"

if [[ ! -f .env ]]; then
    echo "backend/.env.production.example faylidan backend/.env yarating."
    exit 1
fi

if ! grep -Eq $'^APP_ENV=production\r?$' .env || ! grep -Eq $'^APP_DEBUG=false\r?$' .env; then
    echo "backend/.env: APP_ENV=production va APP_DEBUG=false bo'lishi kerak."
    exit 1
fi

if [[ ! -f "$project_dir/frontend/dist/index.html" ]]; then
    echo "Frontend build topilmadi. Kompyuteringizda npm ci va npm run build bajaring."
    exit 1
fi

mkdir -p storage/framework/{cache/data,sessions,views} storage/logs bootstrap/cache resources/views
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
php artisan config:clear

# Mavjud APP_KEY saqlanadi: uni yangilash sessiyalarni buzadi.
if grep -Eq $'^APP_KEY=\r?$' .env; then
    php artisan key:generate --force
fi

php artisan migrate --force
php artisan db:seed --class=ReferenceSeeder --force
php artisan optimize

echo "Backend tayyor. Birinchi o'rnatishda: cd backend && php artisan shifo:create-admin"
echo "Yangilashdan keyin: sudo systemctl reload php8.3-fpm"
