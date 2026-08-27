# Sprint — Actual Bayan Deployment to Serv00

**Date:** 2026-08-27
**Repository:** https://github.com/ezz-coder/edu-platform.git (`main`)
**Current release:** v1.0.0 (latest commit pushed: `7fd9e33`)

## Step 1 — Connection Data Verification

Items checked on this machine:

| Item | Status |
|---|---|
| SSH client | ✅ OpenSSH available (Windows System32 + Laragon git ssh) |
| SSH keys | ❌ NONE found (`~/.ssh` directory does not exist) |
| Git | ✅ configured (user: ezz-coder; GitHub push works — commits `7762efa`, `7fd9e33` pushed successfully) |
| Serv00 hostname | ❌ NOT available anywhere on this machine |
| Serv00 username | ❌ NOT available anywhere on this machine |
| Serv00 credentials/session | ❌ NONE found (no keys, no panel cookies, no stored config in `~/.ssh`, home profile, or `deploy/secrets/`) |
| `deploy/serv00/` package | ✅ present and pushed (`setup-serv00.sh` + `env.production.example`) |

## HARD STOP Point — USER ACTION REQUIRED

Per Sprint rule #17, execution stops here. The SSH key and Serv00 account
identity exist only in the user's Serv00 panel and cannot be created or
guessed by the agent. No secrets are requested in chat.

### What is already done

1. Hosting audit and comparison completed — Serv00 confirmed compatible
   (PHP 8.3, MySQL, SSH, symlinks, $0). See `FINAL_FREE_HOSTING_DEPLOYMENT_REPORT.md`.
2. Deployment automation completely built and pushed to GitHub on `main`:
   - `deploy/serv00/setup-serv00.sh` — one-command server-side setup
     (clone → deploy, .env creation, composer install --no-dev, APP_KEY,
     migrate --force, config/route/view caches, storage:link, permissions,
     docroot re-point to Laravel `public/`)
   - `deploy/serv00/env.production.example` — secret-free template
     (APP_ENV=production, APP_DEBUG=false, DB_CONNECTION=mysql)
   - `FINAL_FREE_HOSTING_DEPLOYMENT_REPORT.md`
3. Production assets verified buildable (`npm run build` ✓ — Vite output generated).
4. No feature deleted, no business logic touched, architecture untouched.
5. Local dev database untouched.

### What is missing — exactly one thing

SSH access to an existing Serv00 account (or the account itself if not yet registered).

### The single step required from the user (~5 minutes)

**If no Serv00 account yet:**

1. Register at https://www.serv00.com/offer (free, no credit card); confirm the email.
2. Panel → **WWW Websites** → add: type *PHP*, address `<username>.serv00.net`,
   PHP version **8.3**.
3. Panel → **MySQL** → enable; create database `<username>_bayan`.

**Then, on your PC, one terminal session:**

```bash
# 1) Connect (use the SSH password from the panel — do NOT paste it anywhere)
ssh <username>@<username>.serv00.net

# 2) Deploy (everything else is automated)
git clone --depth 1 --branch main https://github.com/ezz-coder/edu-platform.git ~/domains/<username>.serv00.net
cd ~/domains/<username>.serv00.net
bash deploy/serv00/setup-serv00.sh <username>

# 3) Edit .env: set DB_PASSWORD to the MySQL password shown in the panel
# 4) Re-run:
bash deploy/serv00/setup-serv00.sh <username>
```

The script prints the final URL: `https://<username>.serv00.net`

**Once this step is done, deployment and smoke test continue automatically from
the script — no other manual intervention is needed.**

## Remaining Steps (Automated After User Action)

All handled by `deploy/serv00/setup-serv00.sh` once SSH works:

- [ ] Clone `main` (verification: deployed HEAD == current GitHub main = `7fd9e33`)
- [ ] PHP 8.3 environment + Composer dependencies (--no-dev)
- [ ] Production `.env` (from template: APP_ENV=production, APP_DEBUG=false, DB_CONNECTION=mysql)
- [ ] Migrations (safe, new DB)
- [ ] Storage: artisan storage:link + permissions (PDF/image preview and downloads preserved)
- [ ] Web root = Laravel `public/`
- [ ] HTTPS (automatic Let's Encrypt on *.serv00.net)
- [ ] Smoke test: Admin/Teacher/Assistant/Student/Parent login + CSS/JS + upload/download + preview

Note: `DevelopmentDemoSeeder` intentionally refuses to run when
`APP_ENV=production`. If demo accounts are needed for the smoke test, the
project-approved way is to run it temporarily with `APP_ENV=local` on the
server (then set back to production), or create accounts through the
platform's own registration flow.

## Deployment Errors

None yet — execution did not start because of the missing SSH credentials above.

## Feature Loss Verification

Not possible yet — but no feature was modified or removed during this sprint
(only new files under `deploy/serv00/` were added).

## Security

- No secrets in GitHub (commits `7762efa`, `7fd9e33` verified placeholders-only)
- DB password goes directly into the server `.env` (never in chat)
- SSH password kept in the user's own terminal

## Cost

**$0**

## Completion Criteria Status

- [ ] Project live on Serv00 — blocked only on the SSH account step above
- [x] Deployment package ready and pushed
- [x] No secrets in GitHub
- [x] No feature deleted or disabled
- [x] Cost = $0
- [x] This report exists