#!/usr/bin/env bash
# ============================================================
# Bayan Platform — Oracle Always Free VM provisioning script
# ============================================================
# Run as root on a fresh Ubuntu LTS (24.04) Oracle Always Free VM.
#
#   sudo bash deploy/setup-server.sh
#
# This script installs ONLY what the project needs:
#   - Nginx
#   - PHP 8.3 + FPM + required extensions
#   - MySQL 8 (MariaDB alternative)
#   - Composer
#   - Git
#   - Certbot (Let's Encrypt)
#   - Cron (for Laravel scheduler if ever needed)
#
# No queue worker is installed because the project has no queue jobs.
# ============================================================
set -euo pipefail

# --- Config (edit these) ---
APP_DOMAIN="${APP_DOMAIN:-DOMAIN_NAME.duckdns.org}"
APP_DIR="/var/www/edu-platform"
DB_NAME="${DB_NAME:-edu_platform}"
DB_USER="${DB_USER:-edu_platform_app}"
DB_PASS="${DB_PASS:-CHANGE_ME_SECURE_PASSWORD}"
GITHUB_REPO="https://github.com/ezz-coder/edu-platform.git"

echo "==> Updating system packages"
export DEBIAN_FRONTEND=noninteractive
apt-get update -y
apt-get upgrade -y

echo "==> Installing base packages"
apt-get install -y \
    nginx \
    git \
    curl \
    unzip \
    software-properties-common \
    ca-certificates \
    lsb-release \
    gnupg

echo "==> Installing PHP 8.3 + extensions"
add-apt-repository -y ppa:ondrej/php
apt-get update -y
apt-get install -y \
    php8.3-fpm \
    php8.3-cli \
    php8.3-mysql \
    php8.3-mbstring \
    php8.3-xml \
    php8.3-curl \
    php8.3-zip \
    php8.3-gd \
    php8.3-intl \
    php8.3-bcmath \
    php8.3-sqlite3 \
    php8.3-redis

echo "==> Installing Composer"
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

echo "==> Installing MySQL 8"
apt-get install -y mysql-server
systemctl enable --now mysql

echo "==> Creating production database + user"
mysql <<SQL
CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL

echo "==> Cloning repository"
mkdir -p "${APP_DIR}"
git clone "${GITHUB_REPO}" "${APP_DIR}"
cd "${APP_DIR}"
git checkout main

echo "==> Installing Composer dependencies (production)"
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Creating .env from template"
cp deploy/env.production.example .env
# Replace placeholders
sed -i "s|DOMAIN_NAME|${APP_DOMAIN}|g" .env
sed -i "s|CHANGE_ME_SECURE_PASSWORD|${DB_PASS}|g" .env
php artisan key:generate --force

echo "==> Running migrations"
php artisan migrate --force

echo "==> Storage permissions"
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
php artisan storage:link

echo "==> Configuring Nginx"
cp deploy/nginx/edu-platform.conf /etc/nginx/sites-available/edu-platform.conf
sed -i "s|DOMAIN_NAME|${APP_DOMAIN}|g" /etc/nginx/sites-available/edu-platform.conf
ln -sf /etc/nginx/sites-available/edu-platform.conf /etc/nginx/sites-enabled/edu-platform.conf
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl enable --now nginx
systemctl reload nginx

echo "==> Installing Certbot (Let's Encrypt)"
apt-get install -y certbot python3-certbot-nginx
certbot --nginx -d "${APP_DOMAIN}" --non-interactive --agree-tos --redirect \
    --register-unsafely-without-email || true

echo "==> Setting up cron for Laravel scheduler (if ever used)"
(crontab -l 2>/dev/null; echo "* * * * * cd ${APP_DIR} && php artisan schedule:run >> /dev/null 2>&1") | crontab -

echo "==> DONE"
echo "Production URL: https://${APP_DOMAIN}"
