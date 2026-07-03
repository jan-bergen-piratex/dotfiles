---
name: mary-add-website-scope
description: "Use when adding a website repo to Mary scope, enabling website edits, previews, PRs, or deploy smoke tests."
---

# Mary Add Website Scope

Add one website repository to Mary's operating scope without changing Mary's core backend.

## Mode

This is a Codex skill for preparing and verifying Mary website access. It is not a Mary runtime skill and it must not change Mary's model/backend.

## Hard Gate

Before any push, PR, webhook, or preview verification, explicitly ask Jan to grant Mary's GitHub credential access to the target repository. Ask every time, even if Jan might already have done it.

Use this wording:

> Please grant Mary's GitHub credential access to `<owner>/<repo>` before I test push/PR/webhook behavior. I can prepare local docs/config first, but commit/push/PR/preview verification will fail until GitHub permissions are updated.

If webhook management or preview automation is part of the task, also ask Jan for repository admin access for Mary's credential, or say that Jan must create the webhook manually.

Do not treat Jan's personal GitHub access as evidence that Mary can use the repository.

## Read First

Inspect the current sources of truth before editing:

- `/home/redbeard/.codex/SOUL.md`
- relevant `AGENTS.md`
- `tech-brain/mary/architecture.md`
- `tech-brain/mary/roadmap/roadmap.md`
- existing Mary website workflow docs in `tech-brain`
- existing repo-specific Mary docs/config for `piratex.com`, `piratesummit.com`, and the target repo, if present
- preview/deployment docs or scripts that mention GitHub webhooks, PR previews, preview domains, or Mary repo paths

Use the current files over chat memory.

## Workflow

1. Identify target facts:
   - GitHub owner/repo
   - production domain
   - preview domain pattern
   - default branch
   - package manager and lockfile
   - install/build/lint/test commands
   - expected deploy and preview mechanism
   - whether PR previews are automatic, manual, or unsupported
2. Ask Jan for the GitHub permission change from the hard gate.
3. Verify Mary's GitHub access with non-destructive checks first:
   - repo can be read
   - branches can be listed
   - Mary's credential can see PR metadata
   - permission-sensitive API checks pass when available
4. Clone or prepare Mary's local workspace using the existing Mary workspace pattern. Do not invent a new repo layout.
5. Add or update Mary workflow docs/config so Mary knows:
   - repo path
   - branch naming convention
   - allowed task types
   - forbidden task types
   - build/lint/check commands
   - PR creation flow
   - preview URL shape
   - known caveats
   - when to stop and ask Jan
6. Run a smoke PR only after GitHub permission exists:
   - create a harmless branch
   - make a tiny reversible/no-op website-safe change
   - commit with Mary-compatible git identity
   - push branch
   - open PR
   - verify build/lint/preview path
   - close PR and delete branch unless Jan asks to keep it
7. If preview automation is expected, verify the full path:
   - GitHub webhook exists or Jan confirms manual creation
   - on Jack, run `/opt/preview-static/bin/preview-reconcile-github-hooks`
   - if the repo is already allowlisted but the hook is missing, run `/opt/preview-static/bin/preview-reconcile-github-hooks --fix`
   - preview webhook receives PR events
   - preview worker queues/builds the PR
   - preview URL returns `HTTP 200`
8. Document blockers exactly. Do not call the integration complete if Mary can only read the repo but cannot push, open PRs, or preview.

## GitHub Permission Checklist

Required for basic Mary website work:

- Repository read access
- Contents write access
- Pull request write access
- Metadata read access

Required for automatic preview/webhook setup:

- Webhook/admin permission, or manual webhook creation by Jan

If GitHub API says `Resource not accessible`, `Not Found` for a known repo, or webhook routes are forbidden, stop and ask Jan to update GitHub permissions.

## Preview Integration Checklist

When the repo should have automatic PR previews, verify the trigger chain instead of only guessing the URL:

- GitHub PR event is emitted.
- Webhook target exists for the repo or organization. On Jack, use `/opt/preview-static/bin/preview-reconcile-github-hooks` as the source-of-truth audit for repos listed in `/opt/preview-static/webhook/server.py`.
- Preview/deployment worker has the repo allowlisted.
- Worker logs show the PR build was queued.
- Preview build exits successfully.
- Preview root and changed route return `HTTP 200`.

For static preview smoke tests, `/opt/preview-static/bin/preview-webhook-smoke <owner>/<repo> <pr> <sha>` sends a signed synthetic `pull_request/synchronize` event through the public webhook URL. Use it only after the repo is allowlisted and a real PR exists; it should queue a build and the worker log should end with `build ok site=<site> pr=<n>`.

If only the root URL works, also test a changed deep route. If both return `404`, inspect webhook/worker logs before blaming the website code.

## Verification

Minimum completion evidence:

- Mary docs/config name the new repo and commands.
- Mary's credential can read the repo.
- Mary's credential can push a branch.
- Mary's credential can create a PR.
- Build/lint commands are known and pass, or failures are documented as repo issues.
- Preview URL is verified with `HTTP 200`, or the missing webhook/admin permission/worker allowlist is documented as the blocker.

Report exact PR number, preview URL, commands run, and any permission Jan had to change.

## Do Not

- Do not put OpenCode or another backend into Mary as her main model.
- Do not bypass GitHub permissions by using Jan's personal credential for the final Mary capability check.
- Do not directly edit live Mary workspaces as the durable source of truth.
- Do not leave smoke PRs open unless Jan asks.
- Do not claim automatic preview works unless an actual PR event produced a served preview.
- Do not continue silently after GitHub permissions fail. Tell Jan the exact missing permission and where he needs to change it.
