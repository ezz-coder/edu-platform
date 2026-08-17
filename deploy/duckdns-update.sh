#!/usr/bin/env bash
# ============================================================
# Bayan Platform — DuckDNS subdomain updater
# ============================================================
# Keeps the free DuckDNS subdomain pointed at this VM's public IP.
#
# Setup (one-time, on the server):
#   1. Create a free subdomain at https://www.duckdns.org
#   2. Copy your DuckDNS token into /etc/duckdns/token (chmod 600)
#   3. Set DUCK_DOMAIN below to your chosen subdomain
#   4. Add to cron:  */5 * * * * /var/www/edu-platform/deploy/duckdns-update.sh
#
# No secrets are stored in this file or in the repository.
# ============================================================
set -euo pipefail

DUCK_DOMAIN="${DUCK_DOMAIN:-DOMAIN_NAME}"          # e.g. bayan
DUCK_TOKEN_FILE="/etc/duckdns/token"

if [[ ! -f "${DUCK_TOKEN_FILE}" ]]; then
    echo "DuckDNS token file not found: ${DUCK_TOKEN_FILE}" >&2
    exit 1
fi

TOKEN="$(cat "${DUCK_TOKEN_FILE}")"

# Get current public IP
PUBLIC_IP="$(curl -s https://api.ipify.org)"

# Update DuckDNS
curl -s "https://www.duckdns.org/update?domains=${DUCK_DOMAIN}&token=${TOKEN}&ip=${PUBLIC_IP}" \
    > /tmp/duckdns-update.log

echo "DuckDNS updated: ${DUCK_DOMAIN}.duckdns.org -> ${PUBLIC_IP}"