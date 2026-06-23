---
name: omclub-website-ops
description: Use when investigating, debugging, documenting, or preparing fixes for the OMClub website, especially omclub.de booking, sponsor upload, Google Apps Script handoff, All-Inkl PHP API endpoints, SMTP bridge, or OMClub deployment/log access. Trigger on OMClub website, omclub.de, booking failures, sponsor booking, sponsoring booklet, sponsor upload, checkout backup, or All-Inkl OMClub runtime.
---

# OMClub Website Ops

Use this skill to get oriented fast and avoid rediscovering the OMClub runtime.

## First Reads

1. Read `/home/jan/.codex/codex-notes/omclub-website-investigation.md`.
2. If working on live behavior, inspect current production files before trusting old notes.
3. If working on source changes, first locate the actual OMClub website source repo. `/home/jan/pirate/piratex.com` is not the booking app source; it only contains OMClub case-study/content references.

## Live Access

- SSH host: `w010c8ea.kasserver.com`
- SSH user: `ssh-w010c8ea`
- SSH key: `/home/jan/.ssh/id_ed25519_gic_allinkl`
- Web root: `/www/htdocs/w010c8ea/web/omclub.de`
- Logs: `/www/htdocs/w010c8ea/logs/access_log_omclub_de_YYYY-MM-DD.gz`

SSH pattern:

```bash
ssh -F /dev/null \
  -i /home/jan/.ssh/id_ed25519_gic_allinkl \
  -o IdentitiesOnly=yes \
  -o BatchMode=yes \
  -o ConnectTimeout=10 \
  -p 22 \
  ssh-w010c8ea@w010c8ea.kasserver.com
```

## Runtime Map

- The live OMClub website is static Astro-style output served from All-Inkl.
- Booking has no local DB in the deployed code.
- Booking state is browser `sessionStorage`, key `om-checkout-data`.
- Product catalog and current price phase are embedded in `/_astro/CheckoutStore*.js`.
- Final booking submit tries Google Apps Script and starts a PHP email backup.
- `/api/checkout_backup.php` sends backup mail to `sponsoring@omclub.de`.
- `/api/send-email.php` is an SMTP bridge used by Google Apps Script.

## Booking Failure Checks

When investigating booking failure, check these before wider speculation:

1. Current final-step bundle:
   - grep for `publicAppsScriptMasterUrl`
   - grep for `backupPromise`
   - verify any referenced Apps Script URL variable is defined in the loaded page or module
2. Current live file mtimes:
   - `booking/index.html`
   - `en/booking/index.html`
   - `_astro/Step4Summary*.js`
   - `api/checkout_backup.php`
3. Logs:
   - booking page views
   - `POST /api/checkout_backup.php`
   - `GET /booking/success/`
   - `POST /api/send-email.php`
4. Do not POST synthetic bookings to `/api/checkout_backup.php` unless Jan explicitly accepts operational noise, because it sends real email and may alert Slack.

Useful log count command:

```bash
cd /www/htdocs/w010c8ea
for f in logs/access_log_omclub_de_2026-06-0*.gz; do
  day=$(basename "$f" .gz | sed "s/access_log_omclub_de_//")
  views=$(zgrep -c "GET /booking/ HTTP" "$f" || true)
  posts=$(zgrep -c "POST /api/checkout_backup.php" "$f" || true)
  success=$(zgrep -c "GET /booking/success/ HTTP" "$f" || true)
  sends=$(zgrep -c "POST /api/send-email.php" "$f" || true)
  printf "%s views=%s backup_posts=%s success_pages=%s appscript_send_email=%s\n" "$day" "$views" "$posts" "$success" "$sends"
done
```

## Safety

- Never print secret values from `.env` or `api/secrets.php`.
- `.env` is in webroot but protected by `.htaccess`; still treat that layout as fragile.
- Keep investigation separate from fixes unless Jan asks to change production.
- Prefer source-repo fixes plus deploy over hand-editing live bundled JS.

## Output

For an investigation report, include:

- confirmed runtime shape
- evidence table from logs or files
- primary cause with exact file/function
- secondary risks
- proposed fixes in priority order
- what was not verified

