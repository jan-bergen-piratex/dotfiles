---
name: autonomous-todo-runner
description: "Use when Jan asks Codex to choose or execute one todo from notes/todos.md via autonomous subagent work."
---

# Autonomous Todo Runner

## Core Rule

Turn exactly one todo into executed work. Prefer a subagent for the todo itself,
then integrate and verify the result in the main thread.

Do not write handoff prompt files to `notes/prompt_files/`. Do not return
`xclip` commands. Those were the old workflow and are now deprecated unless Jan
explicitly asks for a prompt/clipboard handoff.

## Inputs

Require one todo at hand.

- If Jan gives exact todo text, use it.
- If Jan gives a fragment, read `/home/jan/pirate/notes/todos.md` and resolve the exact todo.
- If zero or multiple todos match, ask Jan to choose one.
- Preserve Jan's wording in the prompt; do not over-polish the task until it loses intent.

## Feasibility Gate

Before spawning work, decide whether the todo can plausibly be completed by an
LLM/subagent with local files and available tools.

Proceed only if all are true:

- the success condition can be stated clearly;
- needed local paths, repos, docs, scripts, or search locations can be named;
- work can proceed without interactive user answers;
- likely commands can run without approval, or failures can be handled by documenting blockers;
- destructive, credential, payment, account, production, or remote-server actions are either out of scope or explicitly bounded;
- validation can be described.

Block autonomous execution if the todo requires:

- a missing business decision;
- live GUI interaction, CAPTCHA, phone, payment, or manual account setup;
- sudo, secrets, credential rotation, or production mutation without explicit prior approval;
- irreversible deletion or deployment with unclear rollback;
- information that is not inferable from local files and is not safe to guess.

If blocked, do not spawn work. Tell Jan in one short paragraph what is missing.

## Delegation

Use `subagent-spawner` and spawn one worker subagent for the todo unless the work
is so small that doing it locally is clearly faster.

Worker prompt must include:

- exact todo text;
- relevant paths and source-of-truth files;
- allowed write scope;
- explicit non-goals;
- validation expected;
- instruction that it is not alone in the workspace and must not revert
  unrelated changes;
- final answer must list changed files and validation performed.

If the todo creates or updates a Codex skill, also tell the worker:

- install directly into `/home/jan/.codex/skills` when writable;
- if `~/.codex` is read-only in the worker sandbox, create a complete skill
  package in a writable directory and return the exact command Jan/main thread
  can run:
  `/home/jan/pirate/scripts/install-codex-skill.sh <skill-dir-or-SKILL.md>`;
- do not treat a draft outside `~/.codex/skills` as installed.

Use explorer/reviewer subagents only when they can run independently and answer
a bounded question. Do not spawn multiple agents for the same unclear todo.

## Safety Defaults

- Keep work scoped to the todo.
- Prefer durable fixes over local patches.
- Inspect before editing.
- Use project conventions.
- For code, run tests or type checks when available.
- For docs/notes, preserve useful old context and avoid duplicate files.
- For git repos, check status before editing and do not discard unrelated dirty work.
- For production, auth, credentials, payments, or deletion, stop and report unless prior explicit approval is present in the prompt.
- If sandbox or approval policy blocks a necessary action, document the exact failed command and continue with the safest useful local work.
- For Codex skill installation blocked by read-only `~/.codex`, use
  `/home/jan/pirate/scripts/install-codex-skill.sh <skill-dir-or-SKILL.md>` as
  the handoff command.

## Integration

When the subagent returns:

- inspect changed files or returned artifacts;
- reject scope creep, bloat, stale assumptions, and weak validation;
- for skill todos, verify the skill is actually under `/home/jan/.codex/skills`
  or run/hand off the installer command above;
- perform any small integration edits in the main thread;
- run or verify validation;
- mark the todo done only when completion is proven and the todo context is clear.

Use `apply_patch` for manual notes/todo/skill edits. Do not use Python for
simple todo edits.

## Final Output

Report:

- todo chosen;
- subagent spawned and responsibility;
- what changed;
- validation performed;
- whether the todo was marked done;
- blockers or follow-up.
