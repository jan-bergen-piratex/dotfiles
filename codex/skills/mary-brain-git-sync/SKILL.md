---
name: mary-brain-git-sync
description: "Use when tech-brain or piratex-brain changes must sync to Mary, Jack, Hermes, cron, or live brain checkouts."
---

# Mary Brain Git Sync

Use this skill whenever Mary should see new `tech-brain` or `piratex-brain` content after a repo change, or when debugging whether Mary is reading stale brain docs.

## Path Model

Jack no longer keeps a separate `/opt/tech-brain` host checkout. It was retired
on 2026-06-08 because it duplicated Mary's live `tech-brain` checkout and caused
operator confusion.

Mary reads these live checkouts on Jack:

- host `/opt/hermes-workspaces/mary-workspace/brain/tech-brain`
- host `/opt/hermes-workspaces/mary-workspace/brain/piratex-brain`
- container `/workspace/brain/tech-brain`
- container `/workspace/brain/piratex-brain`

Authority model:

- `tech-brain` is the technical authority for Mary setup, workflows, skills, infrastructure, scripts, and ops notes.
- `piratex-brain` is company knowledge and operating context.

## When To Sync

Run Mary brain sync after pushing repo changes that Mary should use soon:

- Mary architecture, workflow, prompt, skill, or ops documentation changes.
- Company knowledge changes in `piratex-brain`.
- Security, deployment, or runtime docs that Mary consults.
- Before asking Mary to use a newly changed workflow.
- Any time Mary appears to rely on stale docs.

## Normal Command

Use the local wrapper:

```fish
/home/jan/pirate/scripts/sync-mary-brains.fish
```

If the local repos have known unrelated uncommitted work, pass the explicit override:

```fish
/home/jan/pirate/scripts/sync-mary-brains.fish --allow-dirty-local
```

The override only bypasses local cleanliness checks. It does not bypass Jack live checkout safety.

## Safety Rules

- Sync must be fast-forward only.
- Do not use reset, checkout overwrite, clean, or deletion to force Mary live brain state.
- The Jack live sync script must stop if either live checkout is dirty.
- Dirty or conflicting live changes are evidence to inspect, not something to overwrite.
- Do not edit Mary live brain checkouts directly. Edit local repos, commit, push, then sync.

## Jack Scripts And Cron

The live sync script is:

```text
/opt/jack-cron-jobs/sync-mary-brain-docs.fish
```

Expected cron marker:

```text
mary-live-brain-sync:jack
```

Expected cron line:

```text
*/10 * * * * /opt/jack-cron-jobs/sync-mary-brain-docs.fish >> /opt/jack-cron-jobs/sync-mary-brain-docs.log 2>&1 # mary-live-brain-sync:jack
```

Install or keep this cron only if the live script is strict dirty-safe and uses `git merge --ff-only`.

## Verification

Local verification:

```fish
git -C /home/jan/pirate/tech-brain status --short --branch
git -C /home/jan/pirate/piratex-brain status --short --branch
```

Jack host verification:

```fish
ssh -F /dev/null redbeard@37.120.166.157 fish /opt/jack-cron-jobs/sync-mary-brain-docs.fish --verify-only
```

Mary container verification is built into the live sync script. It compares host live checkout commits with:

```text
/workspace/brain/tech-brain
/workspace/brain/piratex-brain
```

If verification fails, report the exact dirty status, branch, host commit, container commit, and cron state before changing anything.
