#!/usr/bin/env sh
set -eu

echo "[start] Clearing cached config..."
php artisan optimize:clear || true

echo "[start] Running database migrations..."
php artisan migrate --force --seed

echo "[start] Generating Passport keys..."
php artisan passport:keys || true

echo "[start] Clearing cache..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "[start] Starting Laravel server..."
exec php artisan serve --host=0.0.0.0 --port="${PORT:-10000}"
