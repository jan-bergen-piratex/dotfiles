# GIC/GPC Handoff For New Device

from: current Codex session / Jan-assisted
to: incoming Codex session on Jan's new device
date: 2026-06-23
time: 11:59 Europe/Berlin

## Project

GIC/GPC Next.js/Payload rebuild and Coolify runtime.

- Repo: `/home/redbeard/pirate/gic-nextjs`
- GitHub: `PIRATEglobal/gic-nextjs`
- Runtime: Next.js 16, React 19, Payload 3, SQLite
- Hosting: Coolify on Jack VPS
- Current public app domains verified on 2026-06-23:
  - `https://gpc.piratex.com`
  - `https://gic.piratex.com`
- Legacy/deprecated Laravel access noted in older docs as `https://gic-depr.piratex.com`.

## Verified Current State

- Local repo status before this handoff was clean except for being on the review
  branch:
  - branch: `review/stabilize-gic-2026-06-19`
  - HEAD: `2841ab8 fix: harden gic access and ops safety`
  - upstream: `origin/review/stabilize-gic-2026-06-19`
  - `origin/main`: `3b15aed fix: avoid investor dashboard reads for startup reviewers`
- Live health checks on 2026-06-23 returned OK:
  - `curl -fsS https://gpc.piratex.com/api/health`
  - `curl -fsS https://gic.piratex.com/api/health`
- `https://gic.piratex.com/apply?embed=true` now redirects to
  `/apply/gamescom-invest-circle-2026/startup?embed=true` and returns 200 with
  embed-safe CSP:
  - `frame-ancestors` includes `https://www.gamescom.global`,
    `https://gamescom.global`, and `https://gpctest.internals.pirate.builders`
  - no `X-Frame-Options` on `/apply` embed routes
- `https://gic.piratex.com/internal` returns `X-Frame-Options: DENY`.
- Important conflict: older docs still say `gic.piratex.com` is legacy Laravel.
  Current live curls show `gic.piratex.com` is now serving the Next app. Treat
  older domain statements as stale unless reverified.

## Recent Commits And Branches

Important commits visible locally:

- `2841ab8` on `review/stabilize-gic-2026-06-19`:
  `fix: harden gic access and ops safety`.
  - Pushed to origin review branch, not main.
  - Verifications from the prior session: `npm run lint`, `npm run typecheck`,
    `npm run test:run` (111 files / 689 tests), `npm run build`.
  - Changes include:
    - account magic-link replay rejection for startup/investor account links;
    - hydration-safe upload-specific public error copy;
    - legacy import path traversal guard;
    - live scripts refuse ambiguous multiple Coolify containers;
    - `start:safe` uses strict backup instead of `--best-effort`;
    - duplicate-app migration no longer deletes rows implicitly;
    - stale destructive ops-doc wording improved.
- `3b15aed` on `origin/main`:
  `fix: avoid investor dashboard reads for startup reviewers`.
  - Fixed Lara-style reviewer dashboard 403 by avoiding investor dashboard reads
    for startup-only reviewers.
- `05b371f` on `main`:
  `fix: raise upload transport limits`.
  - Raised upload transport limits and added recovery-mail send utility.
- `04fa986` on `main`:
  `feat: improve startup attachment recovery flow`.
  - Added improved missing-attachment flow and live recovery-link script.
- `2996c84` on `main`:
  `chore: add deduplicated restic backups`.
  - Current GIC backup path: SQLite-safe DB copy + `/app/data/uploads` into
    restic repos on Jack and All-Inkl.

## Runtime / Ops Facts

- Coolify app/resource UUID: `y4i4m94tm6svh5y7jk2r3gcs`
- Expected live data path: `/app/data`
- Expected live DB URL shape: `file:/app/data/production.sqlite`
- Persistent volume name seen in docs: `y4i4m94tm6svh5y7jk2r3gcs-gic-data`
- Jack SSH shape: `ssh -F /dev/null redbeard@37.120.166.157`
- Legacy all-inkl SSH shape:
  `ssh -F /dev/null -i ~/.ssh/id_ed25519_gic_allinkl -o IdentitiesOnly=yes ssh-w010c8ea@w010c8ea.kasserver.com`
- Restic backup state:
  - Jack repo: `/home/redbeard/restic-repos/gic-nextjs`
  - All-Inkl SFTP repo: `sftp::/www/htdocs/w010c8ea/kas_backup/gic-nextjs-restic`
  - Restic binary on Jack: `/home/redbeard/bin/restic`
  - Restic password locations, no values:
    - Jack: `/home/redbeard/.config/gic-nextjs/restic-password`
    - local recovery copy: `/home/redbeard/.config/gic-nextjs/restic-password`
  - Nightly cron on Jack:
    `41 2 * * * flock -n /tmp/gic-nextjs-restic-backup.lock /opt/jack-cron-jobs/gic-nextjs-restic-backup.sh >> /opt/jack-cron-jobs/gic-nextjs-restic-backup.log 2>&1 # gic-nextjs-restic-backup:jack`
- Runtime secrets are in Coolify env, not repo docs. Do not print or commit
  secret values. Expected env names are documented in
  `docs/coolify-cd-status.md`.

## Key Decisions

- Direct Coolify auto-deploy from `main` is intentional for now.
- Do not reintroduce GitHub Actions CD unless Jan or Manuel explicitly asks.
- Use `npm run live:backup:restic` before risky production changes, data
  cleanup, mail recovery link generation, or migration work.
- Applicant/public account links are passwordless magic links, not passwords.
- Magic/access links must be two-step:
  - GET only inspects/renders confirm page;
  - POST consumes token and creates session.
- `gic.piratex.com` legacy route compatibility must stay because external
  embeds use old paths like `/apply?embed=true` and `/investor/apply?embed=true`.
- Keep `gpctest.internals.pirate.builders` allowlist until final iframe QA is
  complete.

## Current Todo State For GIC/GPC

This was synced in:

```text
/home/redbeard/pirate/notes/session-bus/sessions/gic/outbox/2026-06-23T06-52-21Z_gic_response.md
```

Recommended canonical GIC/GPC block from that sync:

```md
- [ ] GIC/GPC
  - [ ] Final public GPC smoke: direct startup/investor submit, real gamescom iframe submit, Safari/iOS
  - [ ] GPC end-to-end cutover verification: backend + embed + account flows
  - [ ] Merge/deploy `review/stabilize-gic-2026-06-19` if accepted; then verify live
  - [ ] GIC production readiness
    - [ ] Verify Nicole upload crash with real failing file/browser after upload-limit fix
    - [ ] GIC missing attachment recovery: decide Digital Realm canonical row, generate links after backup, contact affected applicants
    - [ ] Slack ops notifications: decide if wanted; configure `SLACK_WEBHOOK_URL` if yes
  - [ ] Send missing-attachment recovery mails to affected real startups after canonical-row cleanup
  - [ ] gic coolify ops skill splitten
```

Do not edit `/home/redbeard/pirate/notes/todos.md` unless Jan explicitly asks. It
contains many stale rolled-forward GIC aliases; use the sync report above when
curating it.

## Open Work

- Task: Merge/review/deploy hardening branch if accepted.
  Owner: Jan / incoming Codex session.
  Source: branch `review/stabilize-gic-2026-06-19`, commit `2841ab8`.
  Blocker: Jan/Manuel decision whether to merge strict backup gate and
  non-destructive migration behavior into `main`.
  Next action: inspect PR/diff, confirm no concern around boot fail-closed
  backup behavior, then merge to `main` only with normal verification and live
  deploy watch.

- Task: Final public GPC smoke.
  Owner: Jan / incoming Codex session; Safari/iOS may need Jan/manual device.
  Source: current todo block and `docs/handovers/260618_gic_next_big_todos_plan.md`.
  Blocker: real gamescom embed context and Safari/iOS availability.
  Next action: test direct startup/investor submit, real gamescom iframe submit,
  confirmation state, no cutoff/artifacts, Safari/iOS.

- Task: Missing attachment recovery.
  Owner: Jan / Manuel with Codex support.
  Source:
  `docs/handovers/260616_manuel_gic_handover.md`,
  `docs/handovers/260617_missing_attachment_recovery_agent_handoff.md`,
  `docs/handovers/260617_main_codex_session_handoff.md`,
  `docs/handovers/260618_gic_next_big_todos_plan.md`.
  Blocker: decide Digital Realm canonical row and approve contacting real
  applicants.
  Next action: run fresh restic backup, generate recovery links only after
  approval, smoke one real/safe upload, then send recovery mails.

- Task: Nicole upload crash.
  Owner: incoming Codex session with Jan/Nicole evidence.
  Source: `05b371f fix: raise upload transport limits`, `2841ab8` public error
  copy on review branch.
  Blocker: actual failing file/browser not yet captured in evidence.
  Next action: reproduce with the real failing file/browser if possible; if no
  repro after `05b371f`, mark as fixed by upload-limit change and deploy/public
  error-copy improvement if accepted.

- Task: Ops docs reconciliation.
  Owner: incoming Codex session if Jan asks.
  Source: stale sections in `tech-brain/ops/gic-current-state.md` and
  `docs/coolify-cd-status.md`; newer `AGENTS-GIC-OPS.md` changes exist on
  `2841ab8`.
  Blocker: avoid broad doc churn unless Jan asks.
  Next action: update stale `gic.piratex.com` legacy/data-wipe statements after
  branch decision.

- Task: Slack ops notifications.
  Owner: Jan/Manuel decision.
  Source: `gic-nextjs/TODO.md` and current todos.
  Blocker: team must decide whether Slack alerts are wanted.
  Next action: configure `SLACK_WEBHOOK_URL` only if wanted.

- Task: Split `gic-coolify-ops` skill.
  Owner: Codex skill-maintenance session.
  Source: todo row `gic coolify ops skill splitten`.
  Blocker: none technical; needs time and judgment.
  Next action: separate runtime/live ops from repo/dev handoff if it keeps
  growing.

## Read First For Next Session

1. `/home/redbeard/.codex/SOUL.md`
2. `/home/redbeard/pirate/gic-nextjs/AGENTS.md`
3. `/home/redbeard/pirate/gic-nextjs/AGENTS-GIC-OPS.md`
4. This file:
   `/home/redbeard/pirate/gic-nextjs/docs/handovers/260623_new_device_gic_gpc_handoff.md`
5. Todo sync report:
   `/home/redbeard/pirate/notes/session-bus/sessions/gic/outbox/2026-06-23T06-52-21Z_gic_response.md`
6. Runtime state:
   `/home/redbeard/pirate/tech-brain/ops/gic-current-state.md`
7. Deploy handoff:
   `/home/redbeard/pirate/gic-nextjs/docs/coolify-cd-status.md`
8. Attachment/migration handoffs:
   - `docs/handovers/260616_manuel_gic_handover.md`
   - `docs/handovers/260617_missing_attachment_recovery_agent_handoff.md`
   - `docs/handovers/260617_main_codex_session_handoff.md`
   - `docs/handovers/260618_gic_next_big_todos_plan.md`

## Useful Commands

Read-only/local:

```bash
git -C /home/redbeard/pirate/gic-nextjs status --short --branch
git -C /home/redbeard/pirate/gic-nextjs log --oneline --decorate --max-count=25 --all
curl -fsS https://gpc.piratex.com/api/health
curl -fsS https://gic.piratex.com/api/health
curl -fsS -I -L 'https://gic.piratex.com/apply?embed=true'
curl -fsS -I 'https://gic.piratex.com/internal'
```

Verification before merge/deploy:

```bash
cd /home/redbeard/pirate/gic-nextjs
npm run lint
npm run typecheck
npm run test:run
npm run build
```

Production backup before risky work:

```bash
cd /home/redbeard/pirate/gic-nextjs
npm run live:backup:restic
```

## Do Not Change Without Jan/Manuel Discussion

- Do not reintroduce GitHub Actions CD.
- Do not deploy or push to `main` without explicit Jan direction and backup/
  verification appropriate to the change.
- Do not run destructive production DB cleanup without fresh restic backup and
  explicit Jan/Manuel approval.
- Do not send real applicant recovery emails without approval and final
  recipient review.
- Do not remove legacy route compatibility for `/apply` and `/investor/apply`.
- Do not remove `gpctest.internals.pirate.builders` from frame allowlist until
  final iframe QA is complete or Jan says to remove it.
- Do not paste or commit secret values. Mention secret locations only.
- Do not expand `/company/missing-files` into broad profile/lookbook editing
  without product discussion; current recovery flow is intentionally narrow.

## Assumptions / Uncertainty

- I did not verify which image Coolify is currently running; health/header
  checks confirm live behavior, not exact commit. Use SSH/Coolify deploy logs if
  the exact live image matters.
- `2841ab8` is on review branch, not `main`, so its changes should be treated
  as not deployed unless verified otherwise.
- Some docs conflict about `gic.piratex.com`; current live curls on 2026-06-23
  show it serves the Next app.
- I did not inspect or edit `notes/todos.md` during this handoff request except
  using the prior sync report as context.
