# SPRINT 69 — Production Deployment Final Report

> **Status: PARTIALLY COMPLETE — GitHub baseline + deployment package ready.**
> Oracle VM + DuckDNS provisioning is **blocked on account-owner credentials**
> (no OCI CLI/config/keys and no DuckDNS token exist on this machine, and
> secrets must never be typed in chat per project rules).

---

## 1. Git

| Item | Value |
|------|-------|
| Repository | `https://github.com/ezz-coder/edu-platform.git` |
| Branch | `main` |
| Baseline commit | `9942993` — `chore: production deployment baseline` |
| Previous baseline | `7304d80` (sprint 44 checkpoint) |
| Push result | `007e695..9942993 main -> main` (success) |
| Working tree | Clean (no uncommitted source changes) |

### What was committed (28 files, +1953 / -42)

- **15 modified source files** — the completed feature work from prior sprints
  (Assistant/Teacher/Exam controllers, models, blade views, routes).
- **7 new source files** — `EntryController.php`, assistant exam/reservation views,
  auth entry view, student exam review view.
- **`deploy/` package** — production deployment assets (see §10).
- **`.gitignore`** — hardened to block secrets, QA artifacts, cookies, screenshots,
  browser binaries, and runtime files.

### Security verification

- `git ls-files` contains **no** `.env`, cookies, credentials, or key files.
- `.env` is gitignored (`.env`, `.env.backup`, `.env.production`).
- No passwords, API keys, tokens, or SSH keys are in the repository.

---

## 2. Hosting

| Item | Value |
|------|-------|
| Provider | **Oracle Cloud Always Free** (target) |
| Server OS | Ubuntu LTS 24.04 (target) |
| Shape | Ampere A1 (within Always Free quota) |
| **Status** | **BLOCKED — requires account-owner authenticated OCI session** |

### Why blocked

- No OCI CLI installed on this machine.
- No `~/.oci/config` or API key present.
- No `OCI_CLI_*` environment variables set.
- Per project rules, cloud credentials must never be typed in chat.

**Action required by account owner:** provision the Always Free VM via the
Oracle Cloud console (or install OCI CLI and authenticate locally), then run
the included `deploy/setup-server.sh` on the VM.

---

## 3. Server Stack (prepared, not yet installed)

The `deploy/setup-server.sh` script installs **only** what the project needs:

- Nginx
- PHP 8.3 + FPM + extensions (mysql, mbstring, xml, curl, zip, gd, intl, bcmath, sqlite3, redis)
- MySQL 8
- Composer
- Git
- Certbot (Let's Encrypt)
- Cron (Laravel scheduler)

**No queue worker** — the project has no queue jobs (`app/Jobs` does not exist).

---

## 4. PHP / Laravel Compatibility

| Item | Value |
|------|-------|
| PHP (local) | 8.3.30 |
| PHP (composer requirement) | `^8.3` ✅ |
| Laravel (local) | 13.17.0 |
| Laravel (composer requirement) | `^13.8` ✅ |
| Composer deps | `laravel/framework`, `laravel/tinker` (prod) |

No `composer.json` changes were made for deployment. No dependency conflicts
were encountered.

---

## 5. Database

| Item | Value |
|------|-------|
| Local default | SQLite (`DB_CONNECTION=sqlite`) |
| Production target | MySQL 8 (`DB_CONNECTION=mysql`) |
| Production DB name | `edu_platform` (in env template) |
| Production user | `edu_platform_app` (in env template) |
| **Status** | **BLOCKED — requires the VM to be provisioned** |

The `setup-server.sh` creates the production database + user and runs
`php artisan migrate --force`. The local SQLite data would need to be
exported/imported once the VM exists (documented in the runbook).

---

## 6. Domain / Subdomain

| Item | Value |
|------|-------|
| Provider | DuckDNS (free) |
| Target | `<chosen>.duckdns.org` |
| **Status** | **BLOCKED — requires account-owner DuckDNS token** |

The `deploy/duckdns-update.sh` script keeps the subdomain pointed at the VM's
public IP. The token is stored server-side at `/etc/duckdns/token` (chmod 600),
never in the repository.

---

## 7. HTTPS

| Item | Value |
|------|-------|
| Provider | Let's Encrypt (free) |
| Tool | Certbot |
| **Status** | **BLOCKED — requires the VM + domain to exist** |

The Nginx config (`deploy/nginx/edu-platform.conf`) enforces HTTP→HTTPS
redirect and the setup script runs `certbot --nginx`.

---

## 8. Storage / Uploads

- `storage/app/public` is served via `/storage/` in the Nginx config.
- `php artisan storage:link` is run by the setup script.
- Permissions: `chown www-data:www-data storage bootstrap/cache`, `chmod 775`.
- Upload limit: `client_max_body_size 256M` (256 MB — sufficient for PDFs/resources).
- Sensitive dirs (`.env`, `app`, `config`, `database`, `routes`, `storage`, `vendor`, `deploy`) are denied via Nginx `location` rules.

---

## 9. Queue / Scheduler

| Capability | Project usage | Server setup |
|------------|---------------|--------------|
| Queue jobs | **None** (`app/Jobs` absent) | Not installed (correct) |
| Scheduled commands | **None** (`routes/console.php` only has default `inspire`) | Cron installed but no-op |
| Notifications | **None** (`app/Notifications` absent) | Not needed |

Per the sprint rules, no queue/scheduler was added because the project does not
use them.

---

## 10. Deployment Package (committed to repo)

| File | Purpose |
|------|---------|
| `deploy/nginx/edu-platform.conf` | Production Nginx server block (docroot = `public/`, HTTPS redirect, protected dirs, 256M upload) |
| `deploy/env.production.example` | Server-only `.env` **template** (placeholders, no secrets) |
| `deploy/setup-server.sh` | Provisions Oracle VM stack + clones + deploys + certbot |
| `deploy/duckdns-update.sh` | Keeps DuckDNS subdomain pointed at VM IP |
| `deploy/README.md` | Deployment runbook |

---

## 11. Environment Configuration (template)

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://<chosen>.duckdns.org`
- `DB_CONNECTION=mysql`
- `SESSION_DRIVER=database`
- `SESSION_SECURE_COOKIE=true`
- `QUEUE_CONNECTION=sync` (no queue jobs)
- `CACHE_STORE=database`
- `MAIL_MAILER=log` (project does not send mail)

---

## 12. Smoke Test

**NOT PERFORMED** — the production URL does not exist yet (VM not provisioned).

The smoke test checklist (admin/teacher/student/assistant logins, dashboards,
resources, lessons, reservations, exams, question bank, notifications, HTTPS,
assets, uploads) is documented in `deploy/README.md` and must be run once the
VM + domain are live.

---

## 13. Problems Encountered

| # | Problem | Root cause | Status |
|---|---------|-----------|--------|
| 1 | No OCI CLI/config/keys on machine | Oracle account not authenticated locally | **Blocked** — needs account owner |
| 2 | No DuckDNS token | DuckDNS subdomain not created | **Blocked** — needs account owner |
| 3 | Working tree had ~200 QA artifacts | Prior sprint testing left ad-hoc files | **Resolved** — `.gitignore` hardened, none committed |

---

## 14. Files Modified (this sprint)

| File | Change |
|------|--------|
| `.gitignore` | Hardened with Sprint 69 security rules (secrets, QA artifacts, cookies, screenshots, browser binaries) |
| `deploy/nginx/edu-platform.conf` | **New** — production Nginx config |
| `deploy/env.production.example` | **New** — server-only env template |
| `deploy/setup-server.sh` | **New** — VM provisioning + deploy script |
| `deploy/duckdns-update.sh` | **New** — DuckDNS updater |
| `deploy/README.md` | **New** — deployment runbook |

No feature, business-logic, or architecture changes were made.

---

## 15. Cost

| Item | Cost |
|------|------|
| GitHub | $0 |
| Oracle Cloud Always Free VM | $0 (target) |
| DuckDNS | $0 |
| Let's Encrypt | $0 |
| **Total** | **$0** |

---

## 16. Next Steps (account owner)

1. **Oracle Cloud**: create the Always Free VM (Ubuntu LTS, Ampere A1 if available).
   If any cost estimate appears, **stop**.
2. **DuckDNS**: create the subdomain (e.g. `bayan.duckdns.org`) and store the
   token at `/etc/duckdns/token` on the VM.
3. **Deploy**: on the VM run:
   ```bash
   sudo APP_DOMAIN=<sub>.duckdns.org DB_PASS='<secure>' bash deploy/setup-server.sh
   ```
4. **Import data**: export local SQLite → import into MySQL (documented in runbook).
5. **Smoke test**: run the checklist in `deploy/README.md`.
6. **Report back** the production URL so it can be verified and documented.

---

## 17. Completion Criteria Status

| Criterion | Status |
|-----------|--------|
| GitHub repository exists | ✅ |
| Current code saved on GitHub | ✅ (`9942993`) |
| No secrets in repository | ✅ |
| Oracle Always Free VM running | ❌ Blocked (needs account owner) |
| No paid resources | ✅ (nothing provisioned) |
| Laravel running on VM | ❌ Blocked |
| Production DB working | ❌ Blocked |
| Storage/uploads working | ❌ Blocked |
| Nginx working | ❌ Blocked |
| PHP-FPM working | ❌ Blocked |
| DuckDNS subdomain working | ❌ Blocked |
| HTTPS working | ❌ Blocked |
| Public URL opens from outside | ❌ Blocked |
| Auth smoke test passed | ❌ Blocked |
| CSS/JS/assets working | ❌ Blocked |
| Final report contains public URL | ❌ (URL not yet created) |
| Total cost = $0 | ✅ |