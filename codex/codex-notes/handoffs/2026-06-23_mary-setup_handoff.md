# Mary / Hermes setup handoff

from: current Codex session / Jan-assisted
to: incoming Codex session on Jan's new device
date: 2026-06-23
time: 11:59 Europe/Berlin

## Scope

This handoff is about Mary/Hermes setup: Mary runtime on Jack VPS, live brain sync, Mary skills and workflow hardening, website PR/preview workflows, OpenCode as a bounded scrubber/worker, OnDeck lead-enrichment integration, connector/setup todos, and stale todo cleanup.

It is not a handoff for changing Mary's core LLM/backend. That idea is obsolete; OpenCode is only a bounded worker Mary can call from generated programs/workflows.

## Verified current state

- Local date/time checked during handoff creation: `2026-06-23 12:01:20 CEST +0200`.
- Local `tech-brain` repo status:
  - path: `/home/jan/pirate/tech-brain`
  - branch: `main`
  - status: `ahead 1, behind 22`, dirty with many modified/deleted/untracked Mary and lead-enrichment files.
  - recent commit: `adfd189 Harden Mary website command workflow`.
- Local `piratex-brain` repo status:
  - path: `/home/jan/pirate/piratex-brain`
  - branch: `main`
  - status: `behind 8`, dirty with many lead-generation-enrichment changes and archive/untracked files.
  - recent commit: `527fd11 vault backup: 2026-06-12 11:16:19`.
- Live brain sync on Jack verifies cleanly:
  - command: `ssh -F /dev/null redbeard@37.120.166.157 fish /opt/jack-cron-jobs/sync-mary-brain-docs.fish --verify-only`
  - result: `tech-brain host/container verified at 4e82a3c`; `piratex-brain host/container verified at 1934991`.
- Live cron on Jack includes:
  - Mary backup: `/opt/jack-cron-jobs/backup-mary.fish`
  - Mary live brain sync every 10 minutes: `/opt/jack-cron-jobs/sync-mary-brain-docs.fish`
  - Docker resource limit enforcement every 5 minutes: `/opt/jack-cron-jobs/enforce-docker-resource-limits.fish`
  - GIC Next.js restic backup: `/opt/jack-cron-jobs/gic-nextjs-restic-backup.sh`
- Live containers on Jack include:
  - `mary-gateway Up 2 weeks`
  - `mary-dashboard Up 2 weeks`
  - `preview-worker Up 3 weeks`
  - `preview-webhook Up 11 days`
  - `preview-static Up 3 weeks`
  - `ondeck-lead-worker-opencode Up 4 days (healthy)`
  - `trypost-... Up 4 days (healthy)`
  - Twenty worker/app/postgres/redis running
  - Umami and Postgres running
  - Coolify core containers running
- OpenCode in the Mary container is usable:
  - command: `ssh -F /dev/null redbeard@37.120.166.157 docker exec mary-gateway /opt/data/bin/opencode --version`
  - result: `1.16.0`
  - command: `ssh -F /dev/null redbeard@37.120.166.157 docker exec mary-gateway /opt/data/bin/run-opencode-scrubber --self-test`
  - result: `OK`
- OpenCode credential locations, no values included:
  - inside Mary runtime: `/opt/data/.local/share/opencode/auth.json`
  - inside Mary runtime: `/opt/data/.local/share/opencode/account.json`
- Current session-bus audit response already written:
  - `/home/jan/pirate/notes/session-bus/sessions/mary-setup/outbox/2026-06-23T06-52-21Z_mary-setup_response.md`

## Current docs / source-of-truth files to read first

1. `/home/jan/.codex/SOUL.md`
2. `/home/jan/pirate/tech-brain/mary/README.md`
3. `/home/jan/pirate/tech-brain/mary/architecture.md`
4. `/home/jan/pirate/tech-brain/mary/workflows/START_HERE.md`
5. `/home/jan/pirate/tech-brain/mary/skills/live/mary-request-triage/SKILL.md`
6. `/home/jan/pirate/tech-brain/mary/skills/live/mary-website-pr/SKILL.md`
7. `/home/jan/pirate/tech-brain/mary/skills/live/mary-pr-merge-approval/SKILL.md`
8. `/home/jan/pirate/tech-brain/mary/workflows/website-change/website-registry.md`
9. `/home/jan/pirate/tech-brain/mary/roadmap/generic-website-preview-route.md`
10. `/home/jan/pirate/tech-brain/mary/workflows/lead-enrichment/opencode-scrubber-worker.md`
11. `/home/jan/pirate/tech-brain/mary/runtime/ondeck-handoff.md`
12. `/home/jan/pirate/tech-brain/mary/workflows/lead-to-outreach/START_HERE.md`
13. `/home/jan/pirate/tech-brain/mary/workflows/personal-outreach/assistant.md`
14. `/home/jan/pirate/tech-brain/mary/skills/strategy.md`
15. `/home/jan/pirate/notes/todos.md`, especially current `# 2026-06-23` section around Mary/Hermes and Lead enrichment.

Because local brain repos are dirty and behind, check live runtime state before treating local files as fully authoritative.

## Decisions and assumptions

- Mary should operate through explicit workflows and skills, not broad improvisation.
- Website edits should use normal shell/git/file operations and patch-style edits. Mary should not ask end users to approve opaque `execute_code`, heredoc Python/Node evals, or broad script execution for simple website changes.
- Mary may create website PRs, report preview links, and ask for review. Mary must not push to `main`, merge, deploy production, or change infra unless the specific workflow allows it.
- PR merging from Slack is allowed only when explicitly approved by Jan or Manuel in the same thread, with a specific PR.
- Authorized Slack IDs currently documented:
  - Jan: `U0B1FF80PC2`
  - Manuel: `U02511QK6`
- Merge replies should be end-user oriented and stable. Include PR link, repository, changed live page links, and production deploy status. Do not include merge strategy, merge commit hash, or branch cleanup chatter unless asked.
- OpenCode is a bounded scrubber/worker only. It is not Mary's backend, not a replacement for Mary's model, and not a general interactive shell architecture.
- OnDeck is the durable human-review and outbox layer for lead enrichment/outreach. Mary/Hermes may generate work and hand it to OnDeck; Slack approval should not replace OnDeck review for connector sends.
- Existing host cron is legitimate operational state. Do not blindly "move all cronjobs to Hermes"; decide cron policy deliberately.

## Website workflow state

- Approved website repos currently documented:
  - `PIRATEglobal/piratex.com`
  - `PIRATEglobal/piratesummit.com`
- Preview mechanism:
  - GitHub PR webhook -> `preview-webhook` -> queue -> `preview-worker` -> static preview served by `preview-static`.
  - Docs: `/home/jan/pirate/tech-brain/mary/roadmap/generic-website-preview-route.md`
  - Supported repos are allowlisted; new Mary website scope must include GitHub permissions and preview onboarding.
- Open issue:
  - Preview onboarding for new websites and "preview starts only after second trigger" remain hardening work.
- Runtime cleanup issue:
  - Prior audit found live Mary checkout for `piratesummit.com` on `mary/pirate-night-sponsor-logos`, not `main`. Recheck before next website task.
- Known doc cleanup:
  - `website-registry.md` has a `GH_TOKEN=$(...)` style verification snippet. That pattern is bad for Slack/prompt use and should be rewritten to avoid teaching secret-bearing command substitutions.

## Mary skills state

- Runtime skills are available through `/opt/data/skills -> /workspace/brain/tech-brain/mary/skills/live`.
- Live skill tree exposes about 42 `SKILL.md` files and 8 top-level skills:
  - `dogfood`
  - `mary-lead-enrichment`
  - `mary-personal-outreach-handback`
  - `mary-pr-merge-approval`
  - `mary-request-triage`
  - `mary-self-disclosure`
  - `mary-website-pr`
  - `writing-guardrails`
- `mary/skills/strategy.md` still describes only 5 Mary-owned skills. Treat it as stale inventory.
- Open work:
  - prune/default Hermes skill bloat
  - reconcile local/live skill counts
  - update `skills/strategy.md`
  - keep only mature skills visible to Mary unless there is a reason.

## Lead enrichment / OnDeck / OpenCode state

- Current direction:
  - Mary/Hermes owns routing, generated worker programs, evidence, and handoff.
  - OnDeck owns Jobs, WorkItems, human review, approve gate, outbox, and connector caps.
  - OpenCode can be called by generated programs as a bounded scrubber/worker.
- OpenCode technical auth is verified in Mary runtime.
- Existing doc path for OpenCode worker interface:
  - `/home/jan/pirate/tech-brain/mary/workflows/lead-enrichment/opencode-scrubber-worker.md`
- Open product work:
  - first real `contact_enrichment_scrub` job
  - JSON/JSONL validator
  - real operator-created OnDeck campaign proof
  - make `ondeck-lead-worker-opencode` sidecar durable in Coolify/IaC if it is still just a direct Docker container
  - connector live smokes.
- Existing local note with useful but non-source-of-truth status:
  - `/home/jan/.codex/codex-notes/ondeck-prod-lead-worker-plan.md`

## Connectors and runtime surfaces

Keep these open unless fresh evidence proves otherwise:

- Unipile / LinkedIn:
  - OnDeck docs say LinkedIn messages and connection requests can be sent, but first real approved send is still a manual-owner Jan/Manuel milestone.
- TryPost:
  - Container is healthy, but draft smoke and real social platform credentials remain open.
- Gmail API:
  - Send adapter remains open.
- Growth SEO / Analytics:
  - Umami is live; GSC service account and PageSpeed API remain open.
- Tender:
  - eVergabe login, inbox/mail adapter, and document store remain open.
- Mary dashboard:
  - container is running, but domain/login/reverse proxy/file upload/agent creation surfaces remain open.
- Slack:
  - prompt-level response gate exists, but app-level channel support, absent/away routing, notification behavior, and DM policy remain open.

## Todo audit state

- Previous audit response with detailed todo status:
  - `/home/jan/pirate/notes/session-bus/sessions/mary-setup/outbox/2026-06-23T06-52-21Z_mary-setup_response.md`
- Current canonical `todos.md` section:
  - `/home/jan/pirate/notes/todos.md` under `# 2026-06-23`
  - Mary/Hermes block is around lines 3822-3841 in the audit's copy.
- Important cleanup finding:
  - Current `# 2026-06-23` already has a `[q] Rolled-forward aliases retired on 2026-06-23` block.
  - Many older duplicate open rows still exist above the current section; do not carry them forward again.
- Check-off ready from the audit:
  - Mary website workflow: no `execute_code`
  - PR merge approval by Jan/Manuel only
  - core Mary boundaries / website PR workflow documented
  - live brain sync mechanism verified
  - OpenCode auth/wrapper self-test verified, if the todo only asks whether OpenCode is usable from Mary runtime
  - Mary-specific Docker resource limits, if separated from broader VPS work
  - old OpenCode/Gemma backend-swap aliases marked `[q]`
  - old "deploy to domains" skill wording marked `[q]`
- Keep open:
  - Mary skills cleanup
  - new-site preview onboarding hardening
  - `piratesummit.com` runtime repo cleanup back to `main`
  - OnDeck generic Jobs/HITL hardening
  - first real `contact_enrichment_scrub`
  - connector smokes
  - Slack app/DM policy
  - dashboard surfaces
  - local brain repo reconciliation.

## Blockers / risks

- Local `tech-brain` and `piratex-brain` are dirty and behind. Do not push, pull-rebase, or reset without understanding Jan/user changes.
- Live brain sync is healthy, but local repo state is not clean enough to call docs fully current.
- Some connector secrets may live behind OnDeck or Coolify rather than Mary env. Absence from Mary env is not proof of global absence.
- Direct Docker sidecar state for `ondeck-lead-worker-opencode` may be non-durable. Verify its creation path before relying on it after host restart.
- Website PR preview setup depends on allowlists/webhooks. New repos need explicit GitHub permissions plus preview onboarding; do not assume adding repo clone is enough.

## What the next session should do first

1. Read `/home/jan/.codex/SOUL.md`.
2. Read this handoff.
3. Read `/home/jan/pirate/notes/session-bus/sessions/mary-setup/outbox/2026-06-23T06-52-21Z_mary-setup_response.md`.
4. Check current local repo status for `tech-brain`, `piratex-brain`, and `notes`.
5. If continuing todo cleanup, use the `todo-curator` skill and edit only the current canonical `# 2026-06-23` todo section unless Jan asks for historical cleanup.
6. If continuing Mary setup, verify live runtime first with the commands below.

## Useful verification commands

```bash
git -C /home/jan/pirate/tech-brain status --short --branch
git -C /home/jan/pirate/piratex-brain status --short --branch
git -C /home/jan/pirate/tech-brain log --oneline -5
git -C /home/jan/pirate/piratex-brain log --oneline -5
```

```bash
ssh -F /dev/null redbeard@37.120.166.157 "docker ps --format '{{.Names}} {{.Status}}'"
ssh -F /dev/null redbeard@37.120.166.157 fish /opt/jack-cron-jobs/sync-mary-brain-docs.fish --verify-only
ssh -F /dev/null redbeard@37.120.166.157 docker exec mary-gateway /opt/data/bin/opencode --version
ssh -F /dev/null redbeard@37.120.166.157 docker exec mary-gateway /opt/data/bin/run-opencode-scrubber --self-test
ssh -F /dev/null redbeard@37.120.166.157 crontab -u redbeard -l
```

## Do not change or re-litigate without Jan/Manuel discussion

- Do not swap Mary's core LLM/backend to OpenCode.
- Do not bypass OnDeck human review for outreach/connector sends.
- Do not broaden Mary merge rights beyond Jan/Manuel approval.
- Do not let Mary push to `main`, merge, deploy production, or change infra as part of normal website edits.
- Do not approve opaque code execution prompts as a normal UX for end users.
- Do not reset or clean dirty brain repos without explicit approval.
- Do not move/remove host cron jobs just because they are outside Hermes; decide operational ownership first.
- Do not paste or expose token values from Mary/Coolify/GitHub/OpenCode/OnDeck credentials.
