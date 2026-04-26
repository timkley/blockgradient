#!/bin/bash
set -euo pipefail
cd "$(dirname "$0")"

APP_NAME="blockgradient"
DOMAIN="blockgradient.timkley.dev"
APP_DIR="/var/www/${APP_NAME}"
TRAEFIK_DYNAMIC="/home/admin/docker/traefik/dynamic/${APP_NAME}.toml"
PHP="/usr/bin/frankenphp php-cli"
COMPOSER="$(command -v composer)"

cp deployment/traefik.toml "${TRAEFIK_DYNAMIC}"
cp "deployment/${APP_NAME}.service" "/etc/systemd/system/${APP_NAME}.service"
systemctl daemon-reload
systemctl enable "${APP_NAME}.service"

runuser -u admin -- ${PHP} "${COMPOSER}" install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts
runuser -u admin -- ${PHP} artisan package:discover --ansi
runuser -u admin -- npm ci
runuser -u admin -- npm run build

if [ ! -f .env ]; then
    cp .env.example .env
    chown admin:admin .env
    runuser -u admin -- ${PHP} artisan key:generate

    set_env() {
        local key="$1"
        local value="$2"

        if grep -q "^${key}=" .env; then
            sed -i "s|^${key}=.*|${key}=${value}|" .env
        else
            printf '%s=%s\n' "${key}" "${value}" >> .env
        fi
    }

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

    docker exec postgres createdb -U postgres "${APP_NAME}" 2>/dev/null || true

    echo "Initial setup complete."
    echo "Set DB_PASSWORD and any app-specific secrets in ${APP_DIR}/.env, then run migrations and start the service."
else
    runuser -u admin -- ${PHP} artisan migrate --force
    runuser -u admin -- ${PHP} artisan optimize
    systemctl restart "${APP_NAME}.service"
fi
