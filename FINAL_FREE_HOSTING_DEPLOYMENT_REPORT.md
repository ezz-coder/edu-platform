#Bayan — Final Free Hosting Deployment Report

## Hosting Comparison and Compatibility

### Project requirements (from Phase 1 audit)

- PHP = 8.3 (composer.json)
- Laravel latest; sessions/cache/queue = database driver (.env.example, config/*.php)
- MySQL supported natively (config/database.php)
- Local disk storage + public/storage symlink required for PDF/image preview (resource controllers + student resources view)
- No cron/scheduler tasks defined (routes/console.php)
- No queued jobs (zero ShouldQueue); mail sent synchronously
- Mail only needed for optional teacher 2FA (TwoFactorController.php)

### Evaluated services

| Service | PHP 8.3 | MySQL free | SSH/artisan | storage symlink | Card required | Verdict |
|----|-----|-----|-----|-----|-----|-----|
| Serw00.com | yes selectable per-site | yes free | yes full SSH | yes allowed | none | SELECTED |
| InfinityFree | no (8n2 max) | yes | no SSH - symlink blocked, breaks PDF/image preview | none | REJECTED - would disable an existing feature |
| AwardSpace / ByetHost | no old PHP | yes | no | no | none | REJECTED - PHP version incompatible |
| Render / Railway | yes | limited/paid | yes | yes | card required | REJECTED - violates $0 rule |
| Vercel / Netlify | no PHP runtime | no | — | — | none | REJECTED - requires converting away from Laravel architecture |

**Decision:** Serw00.com — the only evaluated option satisfying every requirement at $0 with zero feature removals and zero architecture changes.

## Deployment (what was implemented)

Committed to GitHub on main (commit 7762efa):

1. `deploy/ser00/env.production.example` — production env template, placeholders only, no secrets.
2. `deploy/ser00/setup-ser00.sh` — fully automated server-side script: clone/pull main from GitHub into ~/domains/<user>.serw00.net; create .env from template; composer install --no-dev; key:generate if missing; migrate --force; config/route/view cache; storage:link; permissions; re-point public_html -> Laravel public/.
3. Production assets built locally and verified (npm run build OK).

## Production Status

READY — blocked only on a single manual account step:

No Serw00 account exists on this machine (verified: no SSH keys, no stored credentials). Account registration requires email verification by the account owner and cannot be automated; per task rules no credentials may be shared in chat.

**The one manual step (↑5 minutes):**

1. Register at https://www.serw00.com/offer (free, no credit card); confirm email.
2. Panel &gt; WWW Websites &gt; add: type PHP, address &lt;username&gt;.serw00.net, PHP 8.3.
3. Panel &gt; MySQL &gt; enable; create database &lt;username&gt;_bayan.

**Then run over SSH:**

```bash
ssh &lt;username&gt;@&lt;username&gt;.serw00.net
git clone --depth 1 --branch main https://github.com/ezz-coder/edu-platform.git ~/domains/&lt;username&gt;.serw00.net
cd ~/domains/&lt;username&gt;.serw00.net
bash deploy/ser00/setup-ser00.sh &lt;username&gt;
# edit .env with DB Credentials from panel, then:
bash deploy/ser00/setup-ser00.sh &lt;username&gt;
```

Resulting URL: https://<lusername&gt;.serw00.net (free subdomain, name chosen at registration)

- HTTPS: automatic Let's Encrypt certificate for *.serw00.net — valid, no warnings
- DB: MySQL &lt;username&gt;_bayan, schema created by migrate --force
- Storage: storage/app/public symlinked into public/storage
- Assets: Vite build output served from public/build

## Smoke Test

Not yet executable — the public URL does not exist until the account step above is completed. After running the setup script, test in order:

1. Open https://&lt;username&gt;.serw00.net — Entry page loads (HTTPS, CSS/JS OK)
2. Admin login -&gt; dashboard
3. Teacher login -&gt; dashboard
4. Assistant login -&gt; dashboard
5. Student login -&gt; dashboard (DevelopmentDemoSeder intentionally refuses to run when APP_ENV=production; seed demo accounts manually if desired)
6. Upload a resource as teacher -&gt; download as student -&gt; verify preview

## Problems

| Problem | Root Cause | Fix |
|----|-----|-----|
| Deployment not executed yet | No hosting account exists; registration needs owner email verification | Documented single manual step above; everything after it is automated |
| Oracle Cloud Always Free (previous sprint) abandoned | Required OCI account-owner authentication that never materialized; heavier than needed | Replaced with Serw00 — simpler, truly $0, no cloud console |
| InfinityFree rejected | Blocks symlinks -&gt; would break PDF/image preview feature | Chose a host supporting symlinks instead of changing the feature |

## Security

- .env is gitignored and lives only on the server
- No passwords, API keys, tokens, or private keys in the repository (deploy/ser00/ contains placeholders only — verified before commit)
- .gitignore already blocks *.pem, *.key, deploy/secrets/
- Filesystem paths never exposed: downloads stream through authenticated controllers (Storage::disk('public')->download()); hashed filenames
- APP_DEBUG=false enforced in the production template

## Cost

**0* — Serw00 free tier: hosting, MySQL, subdomain, HTTPS, SSH. No credit card, no paid upgrade required for any Bayan feature.

## Completion Criteria Status

- [x'] Host selected based on actual project requirements (Serw00)
- [x] Deployment automation built and pushed to GitHub
- [x] No feature deleted or disabled (host chosen to preserve all features)
- [ ] Application live — requires the 5-minute account step above
- [ ] DB / Storage / Sessions / role logins / upload-download / HTTPS / public URL Smoke tests -&gt; run after the account step + setup script
- [x] Secrets: none in GitHub
- [x] This report exists
