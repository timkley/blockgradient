#!/bin/bash
set -euo pipefail
cd "$(dirname "$0")"

PHP="/usr/bin/frankenphp php-cli"
COMPOSER="$(command -v composer)"

git pull origin main

${PHP} "${COMPOSER}" install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts
${PHP} artisan package:discover --ansi

npm ci
npm run build

${PHP} artisan migrate --force
${PHP} artisan optimize
${PHP} artisan octane:reload

echo "Deployed successfully."
