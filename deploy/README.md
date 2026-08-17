# Bayan Platform — Production Deployment (Sprint 69)

This directory contains the **project-internal deployment package** for the
production baseline. It is part of the repository so the server setup is
reproducible and versioned.

## Files

| File | Purpose |
|------|---------|
| `nginx/edu-platform.conf` | Production Nginx server block (docroot = `public/`, HTTPS redirect, protected dirs) |
| `env.production.example` | Server-only `.env` **template** (placeholders only, never contains secrets) |
| `setup-server.sh` | Provisions the Oracle Always Free VM stack + clones + deploys |
| `duckdns-update.sh` | Keeps the free DuckDNS subdomain pointed at the VM IP |

## Deployment Flow

```
Local → Git Commit → GitHub (main) → Server (git pull) → Production
```

### 1. GitHub baseline

The current source is committed on `main` as:

```
chore: production deployment baseline
```

### 2. Oracle Always Free VM

Provision a free VM (Ubuntu LTS, ideally Ampere A1 within Always Free quota).
**If any estimated cost appears, STOP — do not create a paid resource.**

### 3. Run the setup script (on the server, as root)

```bash
sudo APP_DOMAIN=<sub>.duckdns.org DB_PASS='<secure-password>' bash deploy/setup-server.sh
```

This installs Nginx, PHP 8.3 + extensions, MySQL, Composer, Git, Certbot,
clones the repo from GitHub, creates `.env`, runs migrations, sets
permissions, configures Nginx, and requests the Let's Encrypt certificate.

### 4. DuckDNS

1. Create the subdomain at <https://www.duckdns.org>.
2. Store the token: `sudo install -m 600 /dev/null /etc/duckdns/token` then paste it.
3. Add cron: `*/5 * * * * /var/www/edu-platform/deploy/duckdns-update.sh`

### 5. Post-deploy smoke test

- HTTPS works (no cert warning)
- CSS/JS/images load
- Admin / Teacher / Student / Assistant logins + logout
- Teacher / Student / Assistant dashboards
- Resources, Lessons, Reservations, Exams, Question Bank, Notifications

## Notes

- No queue worker is installed: **the project has no queue jobs**.
- The Laravel scheduler cron is installed but **the project has no scheduled commands**
  (`routes/console.php` only contains the default `inspire` command).
- No feature or business-logic changes were made for deployment.