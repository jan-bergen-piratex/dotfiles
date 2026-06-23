---
name: workflow-deployment
description: "Use when deploying, promoting, syncing, or validating workflow docs/packages into Mary, Hermes, or runtime brains."
---

# Workflow Deployment

Deploy workflow knowledge as an operational artifact, not as loose notes. The goal is a Mary/Hermes-readable package that matches current truth, avoids stale legacy confusion, and can be verified locally and remotely.

## Trigger

Use this skill when the task involves:

- deploying or promoting a workflow draft into an agent runtime directory
- syncing a project workflow package to Mary, Hermes, or a VPS brain mirror
- preparing a "single scp -r or git equivalent" handoff
- cleaning stale planning docs before deployment
- checking whether deployed workflow docs match current project decisions
- adding deployment status, runtime status, backups, or verification for a workflow

Do not use this skill for ordinary app releases unless the release artifact is primarily workflow documentation or agent instructions.

## Core Stance

Treat deployment as a contract between the source docs, the runtime directory, and the agent that will read them. A deploy is not done until the runtime target is clean, current, and independently verifiable.

## Procedure

1. Define the deployment surface.
   - Identify source repo, source package or draft directory, runtime target, transport, expected branch or commit, and backup location.
   - If the target is Git-managed, prefer Git sync for docs. If it is not Git-managed, use `scp` or `rsync` with a timestamped backup.
   - Keep executable helper scripts separate from docs transport. For VPS helper scripts, use Fish scripts and `scp` to the Jack script archive when available.

2. Establish current truth before editing.
   - Read the workflow entrypoint, current understanding, decision log, checklist, runtime status, deployment draft, and any explicit user corrections from the current chat.
   - Decide which files are canonical and which are legacy, reference-only, or stale.
   - If a source has unique project knowledge, extract it into the current structure. If it does not, remove or demote it.

3. Curate before deploy.
   - Search for stale markers: `draft`, `not promoted`, `legacy`, `TODO`, `TBD`, old paths, obsolete assumptions, wrong runtime status, and old names.
   - Search for unsafe artifacts: secrets, tokens, `.env`, `__pycache__`, `.pyc`, generated logs, raw private data, and temporary files.
   - Keep the directory as small as possible while preserving all relevant behavior and evidence.
   - Give invariants, entrypoints, runtime status, and operational steps proportionate space. Do not let fleshed-out examples dominate the structure.

4. Shape the deployed package.
   - The runtime package needs a clear entrypoint, workflow steps, templates or prompts, artifact guides, runtime status, examples, and toolkit references only when useful.
   - It may reference the wider brain for background, but must carry enough local instructions for Mary to act without guessing the core workflow.
   - Planning docs and deployed docs may coexist, but the deployed entrypoint must not ask Mary to resolve old planning contradictions.

5. Build one execution plan.
   - Bundle local edits, validation, commit/push when needed, remote backup, remote sync, cleanup, and verification into one coherent plan.
   - Use Fish for command scripts. Start scripts with preflight checks, avoid Bash heredocs, avoid secrets, and make outputs concise.
   - Ask for one approval for one coherent remote action when possible.

6. Deploy with backups.
   - For Git mirrors: commit local changes, push, then make the remote fetch/reset or pull to the intended commit. Verify remote HEAD.
   - For direct copy: back up the existing target with a timestamp before replacing files. Do not delete unknown remote content without a narrow path and a backup.
   - If the remote worktree is dirty with unknown user changes, back it up or stop and report the conflict.

7. Verify locally and remotely.
   - Local checks: `git status --short`, `git diff --check`, stale-marker search, unsafe-artifact search, and referenced-file existence.
   - Remote checks: target exists, entrypoint exists, runtime status exists, remote commit or file hash matches, no stale markers in deployed package, no pycache or secret artifacts, and critical lines can be read from the target.
   - If runtime behavior changed, run or request a pilot smoke test and capture feedback as a docs change.

8. Report only deploy-relevant facts.
   - Installed or changed paths
   - Commit hash or copy target
   - Backup path
   - Verification results
   - Remaining runtime risks or missing smoke tests

## Hard Gates

- Do not deploy a package that still describes itself as a draft unless the deployment target is explicitly a draft target.
- Do not include secrets, private tokens, raw uploaded files, or private CRM data in workflow docs.
- Do not mark a runtime integration as available unless a current probe or explicit user evidence says so.
- Do not mark a workflow production-ready merely because the docs synced.
- Do not let old planning files contradict the deployed entrypoint.
- Do not use Git as transport for runnable VPS helper scripts. Use Git for docs and knowledge only.
- Do not finish while a needed remote command or verification session is still running.

## Mary/Hermes Notes

- Prefer a runtime package path that Mary can read directly, such as an approved workflow subdirectory inside the Mary workspace brain.
- Keep `START_HERE.md` or the equivalent entrypoint short and authoritative.
- Keep `runtime-status.md` separate from design intent. Runtime status is allowed to say unavailable, blocked, or unprobed.
- Deployment can be successful while runtime smoke tests are still pending; say that clearly.
- For Jan, always use Fish scripts for bundled command sequences.

## Final Response Shape

Keep the final answer short:

```text
Deployed/created: PATH
Target: PATH_OR_COMMIT
Backup: PATH
Verified: CHECKS
Open: SMOKE_TEST_OR_RUNTIME_RISK
```

