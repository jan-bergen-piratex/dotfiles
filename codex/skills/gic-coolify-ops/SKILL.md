---
name: gic-coolify-ops
description: "Use for GIC Next.js, legacy GIC, Coolify, backups, iframe deploys, applicant exports, or Jack runtime checks."
---

# GIC Coolify Ops

## First Reads

Before changing or advising on GIC infrastructure, read current source docs:

```text
/home/redbeard/pirate/tech-brain/ops/gic-current-state.md
/home/redbeard/pirate/gic-nextjs/docs/coolify-cd-status.md
```

If product/company context matters, read:

```text
/home/redbeard/pirate/piratex-brain/40_Produkte/GIC-Gamescom-Invest-Circle.md
```

Use current GitHub Actions and Coolify evidence when state may have changed.
Do not rely on this skill as fresh runtime truth.

## Mental Model

GIC currently has two separate surfaces:

- Legacy production: Laravel on all-inkl at `https://gic.piratex.com`.
- New app: full Next.js/Payload reimplementation in `PIRATEglobal/gic-nextjs`, hosted on Jack through Coolify.

Do not confuse these:

- all-inkl paths, Laravel artisan, and old Google Drive backup belong to legacy GIC.
- Coolify, `/app/data`, Payload SQLite, and direct Coolify auto-deploy belong to `gic-nextjs`.
- `gic-nextjs` is not a patch deployment of the Laravel repo. It is a reimplementation.

## Safety Rules

- Never write secret values into docs, prompts, commits, logs, or final answers.
- If deployment logs show secrets as Docker ARGs or env output, treat them as exposed and recommend rotation.
- Read-only DB inspection means read-only. Do not patch live server files or mutate prod DB unless Jan explicitly asks.
- When Jan must paste command output manually, give one command at a time.
- If Codex has SSH/GitHub access, prefer running read-only checks directly.
- Use `ssh -F /dev/null` when local SSH config may interfere.
- Do not force Mary live brain sync if Jack live checkouts are dirty. Report blocker.

## Legacy All-Inkl Commands

Known SSH shape:

```bash
ssh -F /dev/null \
  -i ~/.ssh/id_ed25519_gic_allinkl \
  -o IdentitiesOnly=yes \
  ssh-w010c8ea@w010c8ea.kasserver.com
```

Known legacy app facts:

```text
App path: /www/htdocs/w010c8ea/web/gic
DB: /www/htdocs/w010c8ea/web/gic/storage/database/database.sqlite
```

Applicant read-only helper:

```text
/home/redbeard/pirate/scripts/production-applicants.sh
```

Use this helper when Jan asks for current legacy production applicants. It prints startup and investor applicants sorted newest first.

## Next.js/Coolify CD

Current deploy path:

```text
main push
-> direct Coolify auto-deploy
```

This is intentional for internal product testing. Do not reintroduce GitHub
Actions CD unless Jan or Manuel explicitly asks.

Future production invariant:

```text
main push
-> npm ci
-> npm run verify:deploy
-> live SQLite backup succeeds and integrity-checks
-> Coolify webhook deploy starts
-> /api/health smoke test passes
```

Project CD handoff:

```text
/home/redbeard/pirate/gic-nextjs/docs/coolify-cd-status.md
```

Known CD state from 2026-06-05:

- GitHub Actions CD was removed on purpose.
- Direct Coolify auto-deploy from `main` is the current intended deploy path for internal testing.
- Backup, restore drill, SMTP verification, Slack notifications, and migration strategy remain todos before production traffic.
- Active Next.js/Coolify URL is `https://gpc.piratex.com`.
- `https://gic.internals.pirate.builders` is no longer active for GIC Next.js; 503 there is expected after the move.

GitHub Actions secrets are not required while GitHub Actions CD is absent.
Coolify is the expected place for runtime app secrets.

Before saying production deploy safety is done, verify all four:

- GitHub verify job passes.
- Backup file is created in the real running container and is nonzero.
- Coolify deploy starts only after backup success.
- Smoke test passes after deploy.

## Coolify Domain Checks

For domain or SSL bugs:

- Confirm DNS points to Jack.
- Confirm Coolify app domain/FQDN includes the hostname.
- Redeploy after changing Coolify domains.
- Check TLS certificate and `/api/health`.
- For Coolify service templates, do not edit generated env vars such as
  `COOLIFY_URL`, `COOLIFY_FQDN`, `SERVICE_URL_*`, or `SERVICE_FQDN_*` as the
  primary fix. They are derived from the service/application domain field and
  may be UI-locked.
- If a service listens on a fixed internal port, Coolify may require the domain
  field to include that port, for example
  `https://analytics.internals.pirate.builders:3000`. In that UI, the port is
  the internal service port used for routing, not necessarily the external
  browser port. Removing it can trigger "Remove Required Port?" warnings and may
  break generated proxy/env values.
- For an HTTP-only service accidentally exposed without TLS, change the service
  application domain/FQDN from `http://host:port` to `https://host:port`, save,
  and redeploy so Coolify regenerates proxy labels and ACME certificate routing.

Known lesson:

`gpc.piratex.com` pointed at the VPS but served the Traefik default certificate or 503 until the app was redeployed with that domain configured. After the move, `gpc.piratex.com` is the active route and `gic.internals.pirate.builders` returning 503 is not itself a bug.

Umami lesson from 2026-06-09: `analytics.internals.pirate.builders` was healthy
over HTTP but returned HTTPS 503 with the Traefik default certificate because
the Coolify service application FQDN was `http://analytics.internals.pirate.builders:3000`.
The intended GUI value is `https://analytics.internals.pirate.builders:3000`.

## Coolify API Access

Known Jack public IP:

```text
37.120.166.157
```

When Jan enables Coolify API access and asks what to put in the Allowed IPs
field, prefer server-side API execution from Jack:

```text
37.120.166.157
```

Do not assume Jan's current device IP is stable. Office/home IPs can differ and
may change. Only include Jan's current public IP when API calls must originate
from his local machine, and verify it at the time:

```fish
curl -fsS https://api.ipify.org; echo
```

For one-off Codex automation against Coolify API:

- Ask Jan to create the token in Coolify UI under
  `Security -> API Tokens`.
- Use `write` permission first; use `root` only if the API returns a permission
  blocker that specifically requires it.
- Never paste token into chat.
- If API allowlist only contains Jack, ask Jan to store token transiently on
  Jack:

```fish
ssh -F /dev/null root@37.120.166.157
mkdir -p /root/.codex-secrets
read -s -P "Coolify API token: " token
printf "%s" "$token" > /root/.codex-secrets/coolify-api-token
chmod 600 /root/.codex-secrets/coolify-api-token
set -e token
exit
```

Codex should read that file over SSH, use the official Coolify API only, avoid
printing the token, and delete `/root/.codex-secrets/coolify-api-token` after
the setup unless Jan asks to keep it.

Coolify 4.1.0 API gotcha from 2026-06-09:

- `POST /api/v1/databases/{uuid}/backups` creates scheduled backups only for
  standalone database resources.
- That endpoint calls `queryDatabaseByUuidWithinTeam()`, which iterates
  `STANDALONE_DATABASE_MODELS`; service database UUIDs return
  `404 Database not found`.
- Known service DBs affected: Twenty Postgres
  `j10pfb75p8l1a3f92h6u6rqq`, Umami Postgres
  `y2100b31q9eeb2ply6a17x97`, TryPost Postgres
  `y6j18iicr3cjapbg4zdluk9e`.
- If Jan asks to back up Coolify service databases, prefer the Coolify GUI.
  Do not insert directly into Coolify DB unless Jan explicitly approves that
  non-API path after hearing the risk.

## Docs And Handoff

For durable docs:

- Technical runtime/deploy truth goes in `tech-brain/ops/gic-current-state.md`.
- Project CD roadmap goes in `gic-nextjs/docs/coolify-cd-status.md`.
- Product/company context goes in `piratex-brain/40_Produkte/GIC-Gamescom-Invest-Circle.md`.
- Commit and push brain changes before trying Mary sync.
- Run `/home/redbeard/pirate/scripts/sync-mary-brains.fish` after pushed brain changes if Mary should see them.

If sync fails due dirty Jack live checkout, stop. Do not reset or overwrite. Report changed paths from the failure output.

## Verification Habits

Prefer these checks:

```bash
git -C /home/redbeard/pirate/gic-nextjs status --short --branch
curl -fsS https://gpc.piratex.com/api/health
```

For local app changes:

```bash
npm run test:run -- <target-test>
npm run verify:deploy
```

If local Next build hangs in Codex shell but GitHub verify passes, treat local result as inconclusive and GitHub as platform signal. Do not call build failed without an error.

## Common Pitfalls

- Fish `set -e` is not Bash error mode. It erases variables. Use explicit `or fail`.
- Fish `string replace` needs `--` before search text that begins with `-`.
- GitHub Actions can show secrets as empty if missing or environment-scoped incorrectly.
- Coolify can inject secrets as Docker build args if configured that way. That leaks into build logs and image metadata risk.
- `npm ci` failures usually mean `package.json` and `package-lock.json` drift. Regenerate lockfile with the npm version used by the container before changing Dockerfile behavior.
