---
name: skill-author
description: "Use when creating, updating, auditing, or hardening Codex, Mary, or agent skills from reusable workflows."
---

# Skill Author

## Core Stance

Turn proven work into reusable behavior. A skill is not a long prompt; it is a compact operating procedure that helps a future agent do one class of work better than it would unaided.

Jan's defaults:
- quality over quantity
- externalize durable knowledge into files
- prefer blueprints, reproducibility, and compounding effects
- integrate user corrections instead of hiding them
- use project-specific evidence before generic best-practice prose
- prefer Rust/cargo tooling before npm installs
- write skills as mechanics, not vibe or persona

## Graduation Gate

Create or update a skill when at least one condition is true:
- the workflow required several meaningful steps
- the same task keeps being rediscovered
- a tricky error, environment quirk, or user correction should not recur
- a successful project chat revealed a reusable pattern
- Mary or Codex needs a durable capability with clear trigger conditions

Do not create junk skills for one-off trivia. If the work is not reusable yet, add a todo, knowledge note, or prompt instead.

## No Vibe Skills

Skills should read like operating procedures, not manifestos.

Reject or rewrite language that only asks the model to "be" something:
- be critical
- be neutral
- be excellent
- be thoughtful
- be rigorous
- default to quality
- earn trust
- act like a senior reviewer

Convert that language into mechanics:
- required files or evidence to inspect
- decision gates and hard blockers
- score caps or readiness thresholds
- output-shape selection rules, or an exact output shape only when consistency
  matters more than context fit
- verification commands or artifacts
- forbidden shortcuts and filler phrases
- examples of should-trigger and should-not-trigger behavior

If a sentence does not change what the agent reads, blocks, scores, writes, or
verifies, delete it.

## Workflow

1. Classify the skill home.
   - Personal Codex skill: install under `/home/jan/.codex/skills`.
   - Mary skill: write or update the Mary skill package/location relevant to that project.
   - Project-local skill: keep it near the project when it depends on project files or conventions.
   - Shared pattern: default to personal Codex first, then later promote to Mary if it proves useful.

2. Gather evidence.
   - Read the relevant chat context if available.
   - Inspect local project docs, existing skills, prompts, todos, and failure notes.
   - Use official OpenAI/Anthropic/Agent Skills docs for format and platform rules.
   - Read `references/research-baseline.md` only when designing a substantial or controversial skill.

3. Extract the reusable pattern.
   - What should trigger the skill?
   - What should the agent do differently because the skill exists?
   - Which user corrections, edge cases, and gotchas must be preserved?
   - Which steps should remain model judgment, and which should become deterministic scripts?
   - What is explicitly out of scope?

4. Design the package.
   - Use a short lowercase hyphenated name that does not collide with existing skills.
   - Put all trigger logic in the frontmatter `description`.
   - Keep `SKILL.md` focused on essential procedure, gotchas, and verification.
   - Move detailed docs to `references/` and link them with precise "read when" guidance.
   - Add scripts only for fragile, repeated, deterministic work.
   - Do not add README, changelog, or decorative meta-docs.

5. Implement.
   - Use the local system `skill-creator` initializer for new Codex skills.
   - Use `apply_patch` for manual edits.
   - Match existing skill style when updating a local project or Mary skill.
   - Avoid broad refactors while creating a skill.
   - If direct writes to `/home/jan/.codex/skills` are blocked by a read-only
     sandbox view, create the complete skill package in a writable directory and
     give Jan this install command:
     `/home/jan/pirate/scripts/install-codex-skill.sh <skill-dir-or-SKILL.md>`.
     The script installs only into `~/.codex/skills`, replaces the target skill
     directory, and validates with the local `quick_validate.py`.

6. Validate.
   - Run the available skill validator.
   - Check that all referenced files exist.
   - Test trigger quality with a few should-trigger and should-not-trigger prompts.
   - If the skill bundles scripts, run a representative script test.
   - If the skill came from an agent failure, verify the new instruction prevents that failure mode.

7. Update Jan's operating memory.
   - Mark the relevant todo done when the skill exists and validates.
   - Add follow-up skill ideas to `Personal Setup > Skills` or `Mary skills`.
   - Add durable preferences to `knowledge.md` when they are cross-cutting.

## Quality Bar

A finished skill should have:
- clear trigger conditions
- one coherent capability, not a grab bag
- exact steps where consistency matters
- freedom where context-dependent judgment matters
- gotchas from real work
- verification steps
- a compact final report that names path, validation, and remaining risk

Reject a skill draft if it mostly says "be helpful", repeats generic model knowledge, sounds like a persona manifesto, overfits one task, hides assumptions, or bloats future context.

## Jan-Specific Gotchas

- Do not use npm for CLI tooling when a solid Rust/cargo option exists.
- Do not use Python for simple todo edits.
- Do not overwrite user wording just to make it sound polished.
- Do not silently turn Mary-specific skills into personal Codex skills, or the reverse.
- Do not treat old artifacts as useless just because they are old.
- Do not let a skill conceal a wrong initial assumption. Preserve the correction explicitly.
- Do not create a big orchestration framework unless current routing is actually failing.
- Do not ship "vibe cody" skill prose. A skill needs gates, artifacts, and
  verification, not aspirational tone.
- Do not overfit a skill to one rigid answer template unless the task genuinely
  requires a fixed output contract. Prefer rules that let the agent choose
  between tables, prose, findings, fix lists, checklists, or scores based on what
  helps the user's current decision.
- If a subagent creates a Codex skill but cannot install it because `~/.codex`
  is read-only in its sandbox, do not leave the skill as a hidden draft. Report
  the exact installer command Jan can run:
  `/home/jan/pirate/scripts/install-codex-skill.sh <skill-dir-or-SKILL.md>`.

## Output Shape

When done, report:
- installed or changed skill path
- what capability it adds
- validation command/result
- any local notes or todos updated
- sources used when external research informed the skill
