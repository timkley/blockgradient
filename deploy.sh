#!/bin/bash
set -euo pipefail
cd "$(dirname "$0")"

git pull origin main

composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

npm ci
npm run build

php artisan migrate --force
php artisan optimize
php artisan octane:reload

echo "Deployed successfully."
