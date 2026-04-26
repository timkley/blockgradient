#!/bin/bash
set -euo pipefail
cd "$(dirname "$0")"

PHP="$(pwd)/frankenphp php-cli"
COMPOSER="$(command -v composer)"

git pull origin main

${PHP} "${COMPOSER}" install --no-dev --no-interaction --prefer-dist --optimize-autoloader

npm ci
npm run build

${PHP} artisan migrate --force
${PHP} artisan optimize
${PHP} artisan octane:reload

echo "Deployed successfully."
