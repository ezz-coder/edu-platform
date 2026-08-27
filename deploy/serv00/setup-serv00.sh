#!/usr/bin/env bash
# Bayan — Serv00 one-time server setup.
# Run ON the Serv00 SSH shell:  bash deploy/serv00/setup-serv00.sh <username>
set -euo pipefail

SERV00_USER="${1:?Usage: bash setup-serv00.sh <serv00-username>}"
DOMAIN="${SERV00_USER}.serv00.net"
APP_DIR="${HOME}/domains/${DOMAIN}"
REPO="https://github.com/ezz-coder/edu-platform.git"
PHP="php83"

echo "==> Bayan deployment to ${DOMAIN}"
mkdir -p "${APP_DIR}"

# 1) Clone / update repository
if [ -d "${APP_DIR}/.git" ]; then
  echo "==> Repository exists, updating to latest main..."
  cd "${APP_DIR}"
  git fetch origin main
  git reset --hard origin/main
else
  echo "==> Cloning repository..."
  git clone --depth 1 --branch main "${REPO}" "${APP_DIR}"
  cd "${APP_DIR}"
fi

# 2) .env
if [ ! -f .env ]; then
  echo "==> Creating .env from template (fill DB credentials after this!)"
  cp deploy/serv00/env.production.example .env
  sed -i "s/__SERV00_USER__/${SERV00_USER}/g" .env
  echo "!! ACTION REQUIRED: edit ${APP_DIR}/.env and set DB_HOST, DB_DATABASE,"
  echo "!! DB_USERNAME, DB_PASSWORD from the Serv00 panel, then re-run this script."
fi

# 3) Composer production dependencies
echo "==> Installing composer dependencies..."
if command -v composer >/dev/null 2>&1; then
  composer install --no-dev --optimize-autoloader --no-interaction --working-dir="${APP_DIR}"
else
  if [ ! -f composer.phar ]; then
    curl -sS https://getcomposer.org/installer | ${PHP}
  fi
  ${PHP} composer.phar install --no-dev --optimize-autoloader --no-interaction --working-dir="${APP_DIR}"
fi

# 4) Laravel build steps
KEY_SET=$(grep -c '^APP_KEY=base64' .env || true)
if [ "${KEY_SET}" = "0" ]; then
  echo "==> Generating APP_KEY..."
  ${PHP} artisan key:generate --force
fi

echo "==> Running migrations..."
${PHP} artisan migrate --force

echo "==> Caching config/routes/views..."
${PHP} artisan config:cache
${PHP} artisan route:cache || true
${PHP} artisan view:cache

echo "==> Creating storage symlink..."
${PHP} artisan storage:link || ln -sfn "$(pwd)/storage/app/public" public/storage

# 5) Permissions
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

# 6) Point document root at public/
PUBHTML="${HOME}/domains/${DOMAIN}/public_html"
if [ -d "${HOME}/domains/${DOMAIN}" ] && [ ! -L "${PUBHTML}" ]; then
  echo "==> Re-pointing public_html -> laravel public/"
  rm -rf "${PUBHTML}"
  ln -s "${APP_DIR}/public" "${PUBHTML}"
fi

echo ""
echo "=============================================="
echo "Bayan deployed to: https://${DOMAIN}"
echo "First run? Edit .env with real DB credentials,"
echo "then re-run: bash deploy/serv00/setup-serv00.sh ${SERV00_USER}"
echo "=============================================="