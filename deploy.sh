#!/bin/bash
set -euo pipefail
cd "$(dirname "$0")"

APP_NAME="blockgradient"
PNPM_VERSION="11.5.0"
PNPM_PACKAGE="pnpm@${PNPM_VERSION}"
PHP_CLI=(/usr/bin/frankenphp php-cli)
COMPOSER="$(command -v composer)"
export OCTANE_SERVER="frankenphp"

run_pnpm() {
    corepack "${PNPM_PACKAGE}" "$@"
}

git pull origin main

"${PHP_CLI[@]}" "${COMPOSER}" install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts
"${PHP_CLI[@]}" artisan package:discover --ansi

corepack enable
run_pnpm install --frozen-lockfile
"${PHP_CLI[@]}" artisan route:clear
run_pnpm run build

"${PHP_CLI[@]}" artisan migrate --force
"${PHP_CLI[@]}" artisan optimize
"${PHP_CLI[@]}" artisan octane:reload --server=frankenphp

sudo -n cp -f "deployment/${APP_NAME}.service" "/etc/systemd/system/${APP_NAME}.service"
sudo -n systemctl daemon-reload

echo "Deployed successfully."
