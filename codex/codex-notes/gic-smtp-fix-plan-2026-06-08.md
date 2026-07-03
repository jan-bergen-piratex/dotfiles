# GIC SMTP Fix Plan

Date: 2026-06-08

Repo: `/home/redbeard/pirate/gic-nextjs`
Live URL: `https://gpc.piratex.com`
Deploy path: `main` -> direct Coolify auto-deploy

Do not reintroduce GitHub Actions CD unless Jan or Manuel explicitly asks.
Do not print, paste, commit, or document secrets.

## Current Evidence

| Fact | Value |
| --- | --- |
| Live container seen during investigation | `71a0434bf39d` |
| Live image tag seen during investigation | `775cd16a41988bc5cb7e2b685bae69370ce33f85` |
| Boot diagnostic | `[smtp-check] TIMEOUT connecting to w010c8ea.kasserver.com:465 after 5000ms` |
| Manual TCP probe from container | `25 blocked`, `465 blocked`, `587 blocked` |
| Manual TCP probe from VPS host | `25 blocked`, `465 blocked`, `587 blocked` |
| Manual TCP probe from local Codex machine | `25 open`, `465 open`, `587 open` |
| Non-SMTP egress from container | `gpc.piratex.com:443 open`, `google.com:443 open`, `w010c8ea.kasserver.com:443 open`, `w010c8ea.kasserver.com:993 open` |
| Production email delivery rows | `12 failed`, latest errors: `Connection timeout` |
| Separate config issue found | `NEXT_PUBLIC_APP_URL` live env is `https://gic.internals.pirate.builders`, should be `https://gpc.piratex.com` |
| Leading diagnosis | SMTP TCP egress/reachability problem, not credentials |
| Keep | `skipVerify: true` in `payload.config.ts` |

## Current Step Status

| Step | Status | Mode | Meaning |
| --- | --- | --- | --- |
| 1 | done | Jan in Coolify UI + Codex verify | `NEXT_PUBLIC_APP_URL=https://gpc.piratex.com`; rebuild verified live |
| 2 | done | Jan/netcup SCP GUI | Jan deleted the netcup firewall mail block rule |
| 3 | done | Codex autonomy | VPS host and live container can now open SMTP `25`, `465`, and `587`; manual `[smtp-check]` OK on `465` |
| 4 | done | Codex autonomy | Nodemailer `verify()` with live `465/smtps` config succeeded; auth/TLS OK |
| 5 | done | Shared | Jan received real confirmation email; DB has new `sent` delivery rows with message IDs for confirmation and ops notification |
| 6 | not needed | Decision | SMTP unblock worked; HTTPS mail API fallback not needed |

## Latest Evidence After Netcup Firewall Change

| Check | Result |
| --- | --- |
| Live container before redeploy | `49f1bcab8bc5`, healthy |
| VPS host SMTP probe | `25 open`, `465 open`, `587 open` |
| Container SMTP probe | `25 open`, `465 open`, `587 open` |
| Manual reachability script | `[smtp-check] OK — TCP connection to w010c8ea.kasserver.com:465 succeeded in 44ms` |
| Nodemailer auth/TLS verify | `ok: true` on `w010c8ea.kasserver.com:465`, `MAIL_SCHEME=smtps` |
| Direct SMTP send | accepted `1`, rejected `0`, provider response `250 2.0.0 Ok: queued` |
| `/api/health` | HTTP 200 |
| Delivery rows | still only the 12 historical `failed` rows; direct SMTP test does not create app delivery rows |
| New issue found | `npm run email:send-batch` fails in production container because `server-only` is imported from source but absent from runtime dependencies |
| Boot logs | still show old `[smtp-check] TIMEOUT` until app is restarted/redeployed; manual rerun is green |

## Latest Evidence After Redeploy

| Check | Result |
| --- | --- |
| Live container after redeploy | `03028e2d360b`, image `775cd16a41988bc5cb7e2b685bae69370ce33f85`, healthy |
| `/api/health` | HTTP 200 |
| Boot data check | `[data-writable] OK` |
| Boot backup | created `/app/data/backups/gic-2026-06-08T10-11-53-202Z.sqlite`, size `1159168` bytes |
| Boot SMTP check | `[smtp-check] OK — TCP connection to w010c8ea.kasserver.com:465 succeeded in 40ms` |
| App ready | `Ready in 121ms` |
| Delivery rows | still 12 historical `failed` rows; need one product/workflow UI-triggered email to prove recorded delivery path creates a `sent` row |

## Final SMTP Closure Evidence

| Check | Result |
| --- | --- |
| Human receipt | Jan received a real confirmation email from `Emma @ Gamescom Invest Circle <emma@pirate-x.de>` |
| Delivery row counts | `12 failed` historical rows, `4 sent` new rows |
| Latest sent rows | `investor_application_confirmation` to Jan/test addresses and `ops_notification` to ops address |
| Message IDs | present on all latest `sent` rows |
| Current conclusion | SMTP network/config issue is fixed. Remaining question is copy/design of email templates, not transport. |

## Interaction Modes

| Step | Goal | Mode | Who Does What | Output |
| --- | --- | --- | --- | --- |
| 1 | Confirm current deploy/container state | Codex autonomy | Codex SSHes to VPS; inspects container, logs, image commit, env presence without secrets | Exact current runtime state |
| 2 | Confirm SMTP reachability | Codex autonomy | Codex probes `w010c8ea.kasserver.com:465/587` from live app container | `open`, `timeout`, `refused`, or DNS result |
| 3 | Check Coolify env shape | Shared GUI | Jan opens Coolify; Codex names exact keys to inspect/change; no secret paste | Confirm `MAIL_HOST`, `MAIL_PORT`, `MAIL_SCHEME`, username shape |
| 4 | Try env-only fix | Jan GUI + Codex verify | Jan updates Coolify env/deploy; Codex verifies logs and health | `[smtp-check] OK` or continued failure |
| 5 | Test SMTP auth/TLS | Codex autonomy if network opens | Codex runs `verifyMailTransport()` or internal equivalent from container | Auth/TLS diagnosis |
| 6 | Real send test | Shared | Jan gives recipient or uses internal UI; Codex verifies delivery rows/logs | Proof mail actually leaves app |
| 7 | If ports stay blocked | Decision with Jan | Codex proposes provider unblock vs HTTPS mail API; Jan chooses route | Chosen fix direction |
| 8 | If code change needed | Codex autonomy after approval | Codex patches repo, adds tests/diagnostics, runs checks, commits/pushes if requested | Durable code fix |
| 9 | Final verification | Codex autonomy | Codex checks deploy commit, `/api/health`, `[smtp-check]`, real-send result, delivery rows | Closure evidence |

## Proposed Fix Path

1. Run one fresh live probe pass:
   - current container id
   - current image commit
   - redacted SMTP env shape
   - latest `[smtp-check]`
   - direct `465` and `587` TCP probes from the app container
2. If one SMTP port opens:
   - use `465` with `MAIL_SCHEME=smtps`
   - use `587` with `MAIL_SCHEME=smtp`
   - redeploy
   - verify `[smtp-check] OK`
   - run app-level connection/auth check
   - send one real magic-link/access-link email
3. If both SMTP ports stay blocked:
   - treat it as VPS/Coolify outbound SMTP egress block or provider routing issue
   - ask provider/VPS host to unblock outbound SMTP, or switch to HTTPS mail API
   - do not rotate credentials as primary fix
4. Keep both mail paths separate:
   - Payload internal/auth mail in `payload.config.ts`
   - product/workflow mail in `src/lib/email/mail-transport.ts`
5. Keep boot non-blocking:
   - preserve `skipVerify: true`
   - preserve `scripts/release/check-smtp-reachability.mjs`

## Acceptance Criteria

| Check | Required Result |
| --- | --- |
| Coolify deploy | healthy |
| `/api/health` | HTTP 200 |
| `[smtp-check]` | `OK` for chosen mail host/port |
| `verifyMailTransport()` or UI check | success |
| Real email | received by test recipient |
| Delivery record | `sent` row exists with message id or provider result |
| Secrets | no secret values in repo, docs, logs copied to chat, commit messages |

## Notes

- Current evidence says credentials are not the first failure. TCP connect fails before auth.
- Port-only switch is not enough if both `465` and `587` remain blocked.
- If using HTTPS mail API later, plan separate implementation. It affects both Payload auth mail and product/workflow mail.
