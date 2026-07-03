---
name: todo-curator
description: "Use when creating, checking off, marking done, adding, deduping, renaming, reorganizing, or reviewing Jan todos in notes/todos.md."
---

# Todo Curator

## Core Rule

Treat Jan's todo file as an operational memory, not a clean project-management database. Preserve intent, history, status markers, and rough wording unless a change improves current usability.

Default todo file:
`/home/redbeard/pirate/notes/todos.md`

Assume Jan runs `autotodo` every morning. Todo edits must therefore be safe for
daily rollover: open/active items reappear, done/deprecated/moved items do not.

## Workflow

1. Read the current structure before editing.
   - Use `grep -n` for relevant keywords and `sed -n` for surrounding sections.
   - The latest dated section is the active daily workspace by default.
   - Older dated sections are historical snapshots; do not rewrite them unless Jan explicitly asks.

2. Classify the request.
   - "new todo" means add one unchecked item in the best current section.
   - "done", "checked", "check off", or "all of these are done" means update status only for the referenced active items.
   - "curate", "merge", "dedupe", "reorganize", or "clean up" means inspect related todos first, then either propose or make a focused restructure depending on the user's wording.
   - If Jan says "not change yet" or asks for a proposal, do not edit.

3. Place todos by current operational home.
   - Personal machine/account/workflow items belong under `Personal Setup`.
   - Skill creation, skill maintenance, Mary skill sync, and agent capability todos belong under `Personal Setup > Skills`.
   - Security, credentials, server documentation, and architecture inventory belong under `Housekeeping` unless they are today's immediate work.
   - Mary/Hermes product behavior belongs under `Hermes Mary`.
   - Lead generation and enrichment workflow work belongs under `Lead enrichment`.
   - Immediate execution priorities belong under `Today` or `Wirklich Heute` when those sections exist.

4. Merge duplicates conservatively.
   - Keep the most specific wording.
   - Preserve useful sub-bullets by moving them under the surviving item.
   - Preserve status if any duplicate is done, but do not mark active work done unless Jan clearly said it is done.
   - If two items overlap but imply different work, keep both and clarify wording instead of merging.
   - When renaming or replacing a todo, mark the old wording `[q]` instead of deleting it, then add or keep the new wording as the surviving active item. This prevents `autotodo` from carrying the stale wording into the next day.

5. Preserve status semantics.
   - Use `- [ ]` for new todos.
   - Keep existing nonstandard markers such as `[x]`, `[q]`, `[n]`, and `[m]` unless Jan asks to normalize them.
   - Treat `[a]` as a short-lived active/open marker meaning a prompt is currently in progress. `autotodo` carries `[a]` forward like `[ ]`, `[p]`, and `[r]`.
   - If minutes or days have passed, remove `[a]` and return the item to `[ ]` unless Jan confirms the work completed.
   - Use `[q]` for deprecated, renamed, merged-away, or morphed todos that should not reappear after morning rollover.
   - Do not infer completion from context. Jan's explicit statement controls status changes.

6. Edit carefully.
   - Use `apply_patch` for todo edits.
   - Do not use Python for simple todo edits.
   - Keep Markdown indentation stable: top-level sections use `- [ ]`, child todos use two spaces, grandchildren use four spaces.
   - Prefer one focused patch over broad rewrites.

7. Verify.
   - Re-read the edited lines with `sed -n`.
   - Use `grep -n` for moved keywords when deduping.
   - In the final response, state only the meaningful change and link to the file/line.

## Curation Heuristics

Prefer fewer active sections with clearer ownership. Move scattered todos into one obvious home when that reduces future search cost.

Do not erase rough user language just to make it sound polished. Clean wording only when it removes ambiguity, fixes a typo in an active item, or makes duplicates mergeable.

When a todo describes a future reusable agent capability, keep it as a skill todo if the output should teach future Codex sessions how to do a repeated job.

When a todo describes one concrete project task, keep it in that project section even if it mentions a skill or agent.

When a request mixes note-taking and todo changes, add the todo first, then suggest any larger reorganization separately unless Jan asked to execute the reorganization.
