#!/usr/bin/env sh
set -eu

echo "[start] Clearing cached config..."
php artisan optimize:clear || true

echo "[start] Running database migrations..."
php artisan migrate --force

echo "[start] Starting Laravel server..."
exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
