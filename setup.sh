#!/bin/bash
set -euo pipefail
cd "$(dirname "$0")"

APP_NAME="blockgradient"
DOMAIN="blockgradient.timkley.dev"
PORT="8002"
PNPM_VERSION="11.5.0"
APP_DIR="/var/www/${APP_NAME}"
TRAEFIK_DYNAMIC="/home/admin/docker/traefik/dynamic/${APP_NAME}.toml"
PHP_CLI=(/usr/bin/frankenphp php-cli)
COMPOSER="$(command -v composer)"
export OCTANE_SERVER="frankenphp"

as_admin() {
    runuser -u admin -- "$@"
}

set_env() {
    local key="$1"
    local value="$2"

    if grep -q "^${key}=" .env; then
        sed -i "s|^${key}=.*|${key}=${value}|" .env
    else
        printf '%s=%s\n' "${key}" "${value}" >> .env
    fi
}

cp deployment/traefik.toml "${TRAEFIK_DYNAMIC}"
cp "deployment/${APP_NAME}.service" "/etc/systemd/system/${APP_NAME}.service"
systemctl daemon-reload
systemctl enable "${APP_NAME}.service"

ufw allow from 172.16.0.0/12 to any port "${PORT}" proto tcp comment "${APP_NAME} octane" >/dev/null

as_admin "${PHP_CLI[@]}" "${COMPOSER}" install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts
as_admin "${PHP_CLI[@]}" artisan package:discover --ansi
corepack enable
corepack prepare "pnpm@${PNPM_VERSION}" --activate
as_admin pnpm install --frozen-lockfile
as_admin "${PHP_CLI[@]}" artisan route:clear
as_admin pnpm run build

if [ ! -f .env ]; then
    cp .env.example .env
    chown admin:admin .env
    as_admin "${PHP_CLI[@]}" artisan key:generate

    set_env APP_ENV production
    set_env APP_DEBUG false
    set_env APP_URL "https://${DOMAIN}"
    set_env DB_CONNECTION pgsql
    set_env DB_HOST 127.0.0.1
    set_env DB_PORT 5432
    set_env DB_DATABASE "${APP_NAME}"
    set_env DB_USERNAME postgres
    set_env LOG_CHANNEL stderr
    set_env CACHE_STORE redis
    set_env SESSION_DRIVER redis
    set_env REDIS_HOST 127.0.0.1
    set_env OCTANE_SERVER frankenphp

    docker exec postgres createdb -U postgres "${APP_NAME}" 2>/dev/null || true

    echo "Initial setup complete."
    echo "Set DB_PASSWORD and any app-specific secrets in ${APP_DIR}/.env, then run migrations and start the service."
else
    as_admin "${PHP_CLI[@]}" artisan migrate --force
    as_admin "${PHP_CLI[@]}" artisan optimize
    systemctl restart "${APP_NAME}.service"
fi
