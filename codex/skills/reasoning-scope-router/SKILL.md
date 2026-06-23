---
name: reasoning-scope-router
description: "Use when choosing reasoning depth, planning depth, autonomy, tooling, delegation, or review level before acting."
---

# Reasoning Scope Router

## Operating Rule

Classify the request into one primary lane before doing visible work. Reclassify
when inspection proves the scope changed. Do not expose private chain-of-thought;
state the chosen depth only when it changes user-visible workflow.

## Decision Ladder

Ask these in order and take the first matching lane:

1. **D0 direct** - User needs a stable fact, a safe command result, a tiny answer,
   or an obvious one-line note/todo change.
   - Action: answer or run the command directly.
   - Cap: no visible plan, no subagents, no broad search.
   - Validate only if the command or answer depends on local/current state.

2. **D1 narrow** - One file, one typo, one small bug, one obvious config tweak, or
   one active todo checkbox.
   - Action: inspect target and nearest pattern, patch, validate.
   - Cap: no visible plan unless Jan asks or risk is hidden.
   - Ask only if success criteria are genuinely ambiguous.

3. **D2 medium** - Several files, moderate ambiguity, user-facing behavior,
   shared helper changes, or a task where validation choice matters.
   - Action: inspect first, then give or maintain a short 3-5 step plan when it
     helps coordination, then execute.
   - Gate before edit: know target files, success criteria, validation command,
     and rollback or containment surface.
   - Use subagents only for independent read-only investigation or review.

4. **D3 large plan-first** - Prompt systems, workflow docs, skills, agent systems,
   data pipelines, architecture changes, cross-repo work, migrations, or work
   likely to expand past one focused implementation pass.
   - Action: reconstruct requirements, owners/source of truth, constraints,
     failure modes, artifacts to edit, validation, and handoff before building.
   - Gate before edit: state or internally settle the plan, risk list,
     validation method, and stop conditions.
   - For roughly 100-prompts-sized work, bias toward 80 planning / 5 initial
     build / 15 adjustment. Small tasks stay out of this lane.
   - Use subagents for parallel evidence gathering, contrary review, or isolated
     implementation only after main thread can define crisp prompts.

5. **D4 stop or ask** - Destructive, irreversible, production, credentials,
   security, legal/medical/financial stakes, unclear authority, or conflicting
   instructions where a wrong assumption can cause real damage.
   - Action: inspect safe state if useful, then ask for the missing decision or
     refuse unsafe action.
   - Gate: no destructive commands, credential exposure, production mutation, or
     policy-sensitive output without explicit bounded approval.

6. **R review/opinion/readiness** - Jan asks whether something is good enough,
   whether you agree, for a readiness call, or for independent critique.
   - Action: start from 50/50. Inspect artifacts before agreeing. Test strongest
     contrary case.
   - Use `project-reviewer` for substantial project/workflow/deployment reviews.
   - Do not edit during review unless Jan explicitly asks for edits.

## Tooling Gate

Use tools when local/current state could change the answer, when editing files,
when claims can be verified cheaply, or when date/currentness matters. Avoid
tools for D0 conceptual answers where no external state is relevant.

Before file edits:
- Read the smallest relevant context.
- Check dirty/worktree state for repo files.
- Identify validation command or manual check.

After file edits:
- Re-read changed lines or run formatter/tests/checks.
- Report validation and blockers in final response.

## Subagent Gate

Use subagents only when at least one condition is true:
- Work splits into independent evidence-gathering or implementation tracks.
- A reviewer pass can find missed requirements, risks, or trigger failures.
- Search space is broad enough that serial inspection would waste context.
- Jan explicitly asks to spawn, delegate, parallelize, or use subagents.

Do not use subagents for D0 or D1. Do not use them when prompts would be vague,
when agents would edit the same files concurrently, or when main thread lacks
enough context to verify their output.

If Jan explicitly asks for subagents, use `subagent-spawner`. If Jan asks for
compressed subagent output or token saving, use `cavecrew`.

## Ask Gate

Ask Jan only when the missing answer would change architecture, irreversible
actions, cost, credentials, production behavior, public commitments, or success
criteria. For cosmetic choices and low-risk defaults, label the assumption and
continue.

## Output Behavior

- D0/D1: keep final answer minimal; do not narrate the router.
- D2: mention plan only if it helped; include changed files and validation.
- D3: include requirements/source-of-truth summary, major decisions, changed
  files, validation, and unresolved risks.
- D4: name the blocker and exact missing decision.
- R: lead with verdict or findings; include evidence inspected and remaining
  uncertainty.

## Trigger Checks

Should trigger:
- "choose how much reasoning this needs"
- "make a plan for a large Codex build"
- "should we use subagents?"
- "review whether this workflow is ready"
- "do this agent-system refactor but do not overprocess tiny tasks"

Should not trigger:
- "what time is it?"
- "add this one todo"
- "fix this one typo"
- "run `date`"
- "change this CSS color in one file"
