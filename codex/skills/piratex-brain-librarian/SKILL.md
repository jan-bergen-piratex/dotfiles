---
name: piratex-brain-librarian
description: "Use when curating, deduping, reorganizing, or auditing piratex-brain, Mary docs, or agent-readable knowledge."
---

# Piratex Brain Librarian

## Stance

Curate `piratex-brain` and Mary documentation as a librarian, not a rewrite engine.

Default to:
- minimum bloat
- no duplicate knowledge
- narrow edits
- evidence before restructuring
- preservation before deletion
- current structure over inherited assumptions

Old docs are not automatically wrong. Current-looking structures are not automatically valid.

## Hard Execution Rule

Never change `piratex-brain` or Mary documentation directly.

This skill may inspect files, classify findings, propose changes, and design the exact cleanup. When the user wants changes executed, use `codex-cli-temp-script-workflow` to create a Fish script that realizes all proposed changes.

The generated script must:
- contain every planned write, move, backup, or deletion
- include preflight checks before mutation
- use Fish and basic Unix commands only
- not use Python, Perl, Node, Ruby, or ad hoc helper languages
- store generated/replacement document contents in Fish variables before writing
- avoid heredocs
- be reviewable as the concrete implementation plan before or during execution

The main agent may use `apply_patch` only to create or update the script file itself or to update skills. It must not use `apply_patch` to directly mutate the target documentation.

## Batch Execution Rule

For librarian cleanup work, optimize for one planned execution, not many small
approvals.

Default workflow:

1. Do the analysis pass first.
   - Use read-only commands and parallel reads to gather all evidence.
   - Inspect local repos, relevant remote/VPS state, stale references, dirty
     worktrees, and likely verification needs before writing any mutation
     script.
   - Do not start fixing while still discovering the shape of the problem.
2. Build one complete Fish executor script.
   - The script should contain all planned local documentation edits, backups,
     staging/commit/push steps if requested, remote/VPS cleanup, sync steps, and
     verification checks.
   - Prefer a local driver script run from Jan's machine when that can safely do
     the work, including local repo edits, Git commits/pushes, and SSH-triggered
     checks.
   - Do not use the VPS transport path merely because this task mentions Mary,
     Jack, or the script archive. Use VPS helper scripts only when the target
     state actually lives on the VPS or a remote-only command must run there.
   - If VPS work is needed, still prefer one local driver script that copies any
     required remote Fish helper and runs it, so the user approves one execution
     command for the whole operation.
   - Include all preflights up front: expected repo state, dirty-file overlap
     checks, required binaries, SSH/VPS reachability, branch/remote checks, and
     target path existence.
   - Include final verification inside the same script: targeted stale scans,
     `git diff --check`, expected branch/head checks, Mary container read
     checks, and any GitHub API smoke check relevant to the cleanup.
3. Execute once when possible.
   - Prefer a single `fish /tmp/name.fish` command for the whole mutation phase.
   - Avoid a chain of separate `scp`, `ssh`, `git`, and verification approvals
     when one reviewed script can safely do the work.
   - If execution reveals a new required fix, do not patch with ad hoc commands.
     Stop, summarize the blocker, revise the full script, and run one new
     complete execution.

Exceptions:

- Tiny, local-only doc fixes may still use one small Fish script.
- Destructive, auth-sensitive, or unclear work may be split only when there is a
  concrete safety reason; state that reason before executing.
- Read-only discovery commands do not need to be collapsed into the executor,
  but they should be batched and kept sparse.

## Intent Gate

Interpret the user's wording before acting:
- If they ask to review, audit, assess, propose, or plan, do not edit. Return findings and a proposed organization.
- If they ask to clean up, reorganize, consolidate, fix, move, or update, inspect the relevant docs, propose the concrete changes, then generate a script that applies them.
- If the request implies broad rewrites, mass moves, deletion, or changing shared operating memory, ask first unless the user explicitly requested that scope. If approved, still execute via script.

Respect ownership boundaries. Do not touch unrelated skill drafts, todos, or shared files unless asked.

## First Pass

Before editing:

1. Locate the relevant documentation surface.
   - Prefer `rg --files` and targeted `rg` searches.
   - Search for repeated titles, concepts, aliases, and stale folder names.
2. Read the nearby structure before judging a file.
   - Parent directories, indexes, links, and naming conventions matter.
3. Check whether the structure is still true.
   - A directory or doc may exist because of an old assumption.
   - Verify against current files, code, prompts, user instructions, and linked sources.
4. Identify the canonical home for each piece of knowledge.
   - If no canonical home exists, propose one before spreading content.

## Classification Labels

Use these labels for important findings:
- **Current truth**: supported by current docs, code, config, or explicit user instruction.
- **Old assumption**: legacy claim or structure that is contradicted, obsolete, or no longer supported.
- **Duplicate**: same knowledge exists elsewhere; name the better canonical home.
- **Open question**: needs Jan's decision or better evidence.
- **Source/reference**: path, line, link, commit, or user instruction that supports the claim.

Do not over-label every sentence.

## Minimum Bloat Check

Before adding or keeping content, ask:
- Does this contain unique useful knowledge?
- Is it current, legacy-but-useful, or just residue?
- Is this the best home for it?
- Is it duplicated elsewhere?
- Could a short link or pointer replace repeated explanation?
- Is this structure based on current reality or a past organizing idea?
- Would this change make future agents faster and less confused?

Delete only when content is clearly duplicate, obsolete, empty, or explicitly in scope. Otherwise consolidate or mark as an open question.

## Consolidation Rules

When consolidating legacy docs:
1. Inventory sources before moving content.
2. Choose or propose one canonical home.
3. Preserve useful unique claims, examples, links, and decision history.
4. Separate current truth from old assumptions.
5. Replace duplicates with a concise pointer only when a pointer is useful.
6. Update inbound links when files move.
7. Avoid polishing tone unless clarity requires it.

Do not flatten history into a false clean story. If an old assumption explains why docs are shaped strangely, preserve that fact briefly where useful.

## Reorganization Rules

A proper structure should have:
- one obvious home for each durable concept
- stable names that match current reality
- indexes only where they reduce search cost
- links that point to canonical docs, not duplicate summaries
- no folders kept alive only because old assumptions needed them

Prefer small, reversible moves. Avoid broad taxonomy changes unless Jan explicitly asks for a larger reorganization.

## Editing Practice

- Do not edit target docs manually.
- Use `codex-cli-temp-script-workflow` for every documentation mutation.
- For multi-file or VPS-affecting cleanup, generate one comprehensive executor
  script after the analysis pass instead of iterative one-off scripts.
- Keep scripts focused on the requested documentation area.
- Preserve user wording when it carries meaning.
- Do not create README, changelog, or meta-doc bloat.
- Do not introduce a new framework unless the current structure is actually failing.
- If uncertain, leave an explicit open question instead of guessing.

## Verification

Before finalizing:
- Re-run targeted `rg` searches for duplicates and stale names.
- Check that moved or edited links still resolve where practical.
- Review the script before execution and make the script review its own resulting
  diff/status for accidental broad rewrites.
- Confirm useful legacy content was preserved before deletion or consolidation.

Final report should name the script path, the single execution command used,
files changed or proposed, canonical homes chosen, duplicates removed or left
intentionally, legacy content preserved, open questions, and verification
performed.
