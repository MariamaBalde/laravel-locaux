#!/usr/bin/env sh
set -eu

echo "[start] Clearing cached config..."
php artisan optimize:clear || true

echo "[start] Running database migrations..."
php artisan migrate --force

echo "[start] Checking if database needs seeding..."
# Vérifier si on a des utilisateurs (indique que le seeding a été fait)
USER_COUNT=$(php artisan tinker --execute="echo App\Models\User::count();" 2>/dev/null || echo "0")

if [ "$USER_COUNT" = "0" ] || [ "$USER_COUNT" = "" ]; then
    echo "[start] Seeding database..."
    php artisan db:seed --force
else
    echo "[start] Database already seeded (found $USER_COUNT users), skipping seeding..."
fi

echo "[start] Generating Passport keys..."
php artisan passport:keys || true

echo "[start] Clearing cache..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "[start] Starting Laravel server..."
exec php artisan serve --host=0.0.0.0 --port="${PORT:-10000}"
