# OMClub PR #1 vs Booking Failure Diagnosis

Date: 2026-06-09

Scope: Compare PR `#1 feat: SQLite journal as durable submission record` against `/home/jan/.codex/codex-notes/omclub-website-investigation.md`.

## Verdict

PR #1 is useful durability work, but it does not fix the current booking failure.

It fixes one diagnosed architectural weakness: missing durable server-side record.

It does not fix the immediate frontend break:

- `publicAppsScriptMasterUrl` is still referenced inside a plain browser `<script>` without being injected.
- `backupPromise` is still declared inside `try` and used in `catch`, where it is out of scope.
- PR #1 adds `ackAppsScript` inside `try` and calls it in `catch`, creating another out-of-scope catch reference.

## Diagnosis Coverage

| Diagnosis Item | PR #1 Status | Evidence |
|---|---|---|
| Missing browser injection for Apps Script URL | Not fixed | `Step4Summary.astro` still defines `publicAppsScriptMasterUrl` in frontmatter lines 10-12 and uses it in plain `<script>` around line 623. |
| `backupPromise` out of scope in `catch` | Not fixed | PR branch still declares `const backupPromise` inside `try` around line 633 and uses it in `catch` around line 718. |
| Fallback alert can fail before showing user guidance | Not fixed | Catch path now calls `ackAppsScript(...)` around line 714 before `await backupPromise`; both names are scoped inside `try`, so catch can still throw. |
| No durable server-side booking record | Fixed in direction | Adds `public/api/db.php`, journals checkout payload before mail in `checkout_backup.php`, stores SQLite rows under `_private/db/omclub.sqlite`. |
| Backup endpoint returns no recovery handle | Partly fixed | `checkout_backup.php` now returns `idempotency_key` on success and failure. |
| Need distinguish Apps Script accepted vs failed | Partly fixed | Adds `/api/journal_ack.php` and client ack logic, but client ack is blocked by frontend scope bugs unless fixed first. |
| Need build/deploy gate | Partly fixed | Adds PHP syntax gate only. Does not add a JS/browser bundle gate for `publicAppsScriptMasterUrl` or `backupPromise`. |
| Old `/booking-en/` links 404 | Not fixed | PR does not touch redirects or legacy routing. |
| VAT ID required may block buyers | Not fixed | PR does not touch billing form requirements. |
| Secrets duplicated in `.env` / `api/secrets.php` under webroot | Not fixed | PR does not consolidate secrets. |

## Unrelated or Broader PR Work

- Journals non-booking endpoints: `contact.php`, `subscribe.php`, `sponsoring.php`, `testimonial.php`, `backup-upload.php`.
- Adds a generic SQLite helper and schema with future lifecycle columns.
- Adds Apps Script ack endpoint.
- Adds `_private/**` FTP deploy excludes.
- Adds PHP syntax checking in GitHub Actions.

## New Risks or Questions

- SQLite/PDO availability on All-Inkl must be verified. PR is designed fail-open, so a missing driver should not break bookings, but it would also mean the new durability feature silently does not persist except for Slack/error logs.
- `_private/.htaccess` uses `Order allow,deny` / `Deny from all`. This may be okay on All-Inkl, but Apache 2.4 normally prefers `Require all denied`. Verify `https://omclub.de/_private/db/omclub.sqlite` returns forbidden after deploy.
- `/api/journal_ack.php` can be called by anyone who knows or guesses an idempotency key. PR notes this only flips Apps Script status on one row. That is acceptable if this field is treated as advisory, not authoritative.
- The PR states "Every endpoint inserts before Apps Script / mailer / Sendy", but checkout's Apps Script call is client-side. The journal insert happens through `checkout_backup.php`, so if frontend JS fails before `checkout_backup.php`, no row exists. This is exactly the current failure mode.

## Merge Guidance

Do not merge PR #1 as the fix for the current failed bookings.

Either:

1. Patch PR #1 before merge with the immediate frontend fixes:
   - inject Apps Script URL into browser JS via `define:vars` or data attribute,
   - declare `backupPromise` and `ackAppsScript` outside the `try` block,
   - add a build/test gate for these two regressions.

2. Or make a smaller hotfix PR first that only fixes final submit and adds tests, then merge the journal PR after review.

