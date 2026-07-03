---
name: handoff-receiver
description: "Use when Jan asks what was handed off, which handoffs are current, or what todos come from handoffs."
---

# Handoff Receiver

Use this skill to turn scattered handoff documents into Jan's current pickup list.

## Scope

Default search roots:
- `/home/redbeard/pirate/tech-brain`
- `/home/redbeard/pirate/piratex-brain`
- `/home/redbeard/pirate/gic-nextjs`
- `/home/redbeard/pirate/gic`
- `/home/redbeard/pirate/ondeck`
- `/home/redbeard/pirate/projects`

Search filenames and content for:
- `handoff`
- `handover`
- `hand-off`
- `hand over`
- `übergabe`
- `uebergabe`

Exclude generated or dependency noise such as `.git`, `node_modules`, `.next`,
`vendor`, `target`, `__pycache__`, logs, caches, and raw pipeline outputs unless
Jan explicitly asks for every artifact.

## Currentness

Classify every relevant handoff:
- **Current pickup**: fresh enough for today, still has open next steps, or is the newest doc for that project.
- **Live contract**: durable operational handoff or runtime contract, not a task handoff.
- **Background**: still useful context but superseded by newer docs or already archived.
- **Stale/weekend residue**: written for a past pause and no longer the best entrypoint.
- **Not a real handoff**: protocol docs, CSV data handoffs, workflow step names, or generated logs.

Dates matter. If today is known, compare exact dates. Do not treat an older
handoff as stale only because it is old; check whether a newer doc supersedes it
or whether its open items remain unresolved.

## Provenance And Direction

For each handoff, determine:
- **from**: explicit `from`, `author`, git author, commit source, or a labeled inference.
- **to**: explicit `to`, `for`, audience line, file location, or a labeled inference.
- **date** and **time**: from document header first, then git commit timestamp if absent.
- whether it is probably written by Jan, by Manuel, by a Claude/Codex session with Manuel, or by another agent.
- whether it is directed specifically to Jan, to any incoming agent/person, to Mary/Hermes, or to general project maintainers.

Use git history as supporting evidence when useful:
`git -C <repo> log --follow --format='%h %ad %an <%ae> %s' --date=iso -- <path>`

Do not overstate provenance. Say "probably" or "inference" when the document
does not explicitly name from/to.

## Todo Extraction

When Jan asks for todos according to handoffs:
1. Read only current pickup and live-contract docs first.
2. Extract explicit `next steps`, `open items`, `dangling work`, `blocked`, `not done`, and `needs` sections.
3. Convert each item into a concise task with:
   - source handoff path
   - project
   - owner if stated
   - blocker/dependency if stated
   - whether Jan action is required, agent action is possible, or Manuel/third party is needed
4. Merge duplicates across handoffs, preserving the most specific source.
5. Separate immediate Jan decisions from agent-executable work.

Do not edit `/home/redbeard/pirate/notes/todos.md` unless Jan explicitly asks to add
or check off items. If editing todos, use the `todo-curator` skill too.

## Output

Choose the shape that answers Jan's question. For broad triage, prefer a compact
table with:
- file
- currentness
- from
- to
- date/time
- action summary

For "what do I need to do", lead with the actionable list and keep provenance
below it.

Always make unclear fields explicit:
- `from: inferred`
- `to: inferred`
- `time: missing in doc, commit time used`

## Verification

Before finalizing:
- Re-run a targeted filename/content search if the answer claims "all".
- Check the newest relevant git commits when provenance or currentness matters.
- Link each real local handoff file with an absolute markdown file link.
