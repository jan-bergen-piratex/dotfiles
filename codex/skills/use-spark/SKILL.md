---
name: use-spark
description: Use when Jan says use-spark, asks to use Codex Spark, is low on tokens or context, wants to save main-agent tokens, wants big work delegated to Spark, or wants a Spark subagent workflow. Spawns a gpt-5.3-codex-spark subagent for bounded large work while the main agent keeps context, stays light, reviews results, integrates changes, and owns risky or live decisions.
---

# Use Spark

## Core Idea

Use Spark as a sidecar worker when Jan wants to preserve the main session's context. A Spark subagent has its own separate context window and token limits; it does not automatically know the full main chat unless the main agent passes a compact prompt, handoff file, or relevant paths.

Spark is good for bounded implementation, codebase inspection, tests, cleanup, and draft generation. The main agent remains responsible for scope, safety, final review, integration, and user-facing decisions.
The main chat should not do major work while Spark is running unless that work is the immediate critical path and cannot be delegated.
The point of this skill is to save main-agent tokens and keep the main thread available for coordination, review, and decisions.

## Workflow

1. Identify the immediate blocking task.
   - Keep the next critical-path step in the main thread.
   - Delegate sidecar work that is large, bounded, and can run independently.

2. Prepare compact context.
   - If the task depends on chat history, write or refresh a short handoff under `/home/jan/.codex/codex-notes/`.
   - Include objective, current state, relevant files, constraints, non-goals, and validation.
   - Do not pass secrets.

3. Spawn one Spark subagent with `multi_agent_v1.spawn_agent`.
   - Set `model: "gpt-5.3-codex-spark"`.
   - Set `reasoning_effort: "xhigh"` unless Jan explicitly asks for something cheaper.
   - Use `agent_type: "worker"` for bounded edits.
   - Use `agent_type: "explorer"` for read-only codebase questions.
   - Keep `fork_context` omitted or `false` unless Jan explicitly wants to spend context copying the current thread.

4. Give strict ownership.
   - Name exact files, directories, or responsibility.
   - Tell the subagent it is not alone in the workspace.
   - Tell it not to revert user changes or unrelated edits.

5. Continue local non-overlapping work while Spark runs.
   - Do not redo the delegated task.
   - Wait only when the next step needs Spark's result.

6. Review and integrate.
   - Inspect Spark's changed files or findings.
   - Reject scope creep, risky shortcuts, stale assumptions, and missing validation.
   - The main agent runs final checks and owns the final answer.

7. Close finished agents when no longer needed.

## Spawn Prompt Template

Use this shape:

```text
You are a gpt-5.3-codex-spark subagent. You have your own separate context window and token limits; do not assume access to the main chat beyond this prompt and referenced files.

Objective:
<bounded task>

Read first:
<files or handoff paths>

Allowed write scope:
<exact paths, or "do not edit files">

Non-goals:
<what not to touch>

Validation:
<commands/checks to run, or explain if not run>

You are not alone in the workspace. Do not revert user changes or unrelated edits. List changed files and validation in your final answer.
```

## Do Not Delegate

Keep these in the main thread unless Jan explicitly approves a different split:

- production database mutations
- live deploys, live email sends, or irreversible ops
- secrets handling
- final security/auth decisions
- broad architecture or product decisions
- tasks that require the whole chat and no handoff exists

## If Subagents Are Unavailable

Write a compact handoff plus ready-to-run Spark prompt under `/home/jan/.codex/codex-notes/`, then tell Jan to start a Spark session manually with that prompt.
