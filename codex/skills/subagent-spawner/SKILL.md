---
name: subagent-spawner
description: "Use when Jan asks to spawn, delegate, coordinate, or split work across Codex subagents."
---

# Subagent Spawner

## Core Rule

Use ordinary subagents for ordinary delegation. Do not reach for cavecrew unless Jan explicitly says `cavecrew`, asks for caveman/compressed subagent output, or asks to save tokens/context.

Subagents are useful when they create parallel progress or isolate a bounded task. They are not a default substitute for thinking or for the main thread doing the immediate critical-path work.

## Before Spawning

1. Identify the immediate blocking task.
   - Keep it in the main thread if the next local step depends on it.
   - Delegate sidecar tasks that can run independently.

2. Split by ownership.
   - Each subagent needs one clear responsibility.
   - For file edits, each subagent should own disjoint files or directories.
   - Tell subagents they are not alone in the workspace and must not revert unrelated changes.

3. Decide the agent type.
   - `explorer`: specific read-only codebase questions.
   - `worker`: concrete implementation, drafting, or file changes with defined ownership.
   - `reviewer` or review-style agent: check a result after implementation when independent review can catch risk.
   - default agent: general bounded work when a special type is not needed.

4. Avoid over-delegation.
   - Do not spawn for one-line answers.
   - Do not spawn if explaining the task would take longer than doing it.
   - Do not spawn multiple agents for the same unresolved question.
   - Do not spawn if results cannot be integrated cleanly.

## Prompt Shape

Give each subagent:
- exact objective
- relevant paths or artifacts
- allowed write scope
- explicit non-goals
- expected output format
- whether to edit files or return a draft only
- validation expected before completion

For coding/editing work, include:

```text
You are not alone in the workspace. Do not revert user changes or unrelated edits.
Own only: <paths/responsibility>.
List changed files and validation performed in your final answer.
```

For draft-only work, include:

```text
Do not edit files. Return final file contents or findings only.
```

## Parallel Patterns

Use parallel subagents when work is independent:
- one skill draft per skill
- one code area per worker
- one investigation angle per explorer
- one reviewer after implementation while the main thread prepares integration

Do not ask several agents to solve the same design problem unless Jan explicitly wants competing options.

## Integration

When results return:
1. Read the returned artifacts or changed files.
2. Do not blindly accept. Check for scope creep, bloat, stale assumptions, and validation gaps.
3. Integrate the best parts in the main thread.
4. Validate the combined result.
5. Close agents that are no longer needed.

If a subagent produces a draft, the main thread owns final installation, patching, validation, and todo updates.

## Cavecrew Boundary

Use `cavecrew` only when Jan explicitly requests:
- `cavecrew`
- caveman-style subagent output
- compressed subagent output
- token/context reduction
- saving context via terse agent results

For generic "spawn subagents", "delegate this", "use workers", or "parallelize", use this skill instead.

## Final Report

Summarize:
- agents spawned and their responsibilities
- what was integrated
- validation performed
- anything left open or blocked
