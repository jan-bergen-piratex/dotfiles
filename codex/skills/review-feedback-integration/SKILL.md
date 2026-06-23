---
name: review-feedback-integration
description: "Use when turning Jan corrections or agent failures into prompt changes, docs, fixtures, skills, or memory updates."
---

# Review Feedback Integration

## Core Rule

Treat user correction as evidence to integrate, not friction to explain away. Preserve the useful lesson, name any bad initial assumption plainly, verify the corrected behavior, and keep the resulting instruction small enough to remain useful.

## Workflow

1. Capture the feedback before changing anything.
   - Identify the original behavior or assumption.
   - Identify what the user corrected.
   - Record the desired future behavior.
   - Keep the correction visible when it matters. Do not rewrite history to make the first attempt look correct.

2. Classify the feedback.
   - `preference`: durable style, tone, tool, format, or workflow preference.
   - `bug`: output, code, script, parser, command, or behavior was objectively wrong.
   - `missing context`: the agent lacked project, user, domain, or environment context that should have been consulted.
   - `false assumption`: the agent inferred something unsupported or skipped verification.
   - `schema issue`: a data contract, field, type, frontmatter, config, API shape, or output format was wrong.
   - `workflow gap`: process order, validation, handoff, or checklist was incomplete.

3. Choose the smallest durable integration target.
   - Change a prompt or skill when future agent behavior should change.
   - Change a heuristic when the right behavior is context-dependent.
   - Add a regression fixture or test when the failure is reproducible.
   - Update docs when humans or future agents need operational context.
   - Update durable memory only when the feedback is stable, cross-session, and likely to improve future work.
   - Do not add memory for one-off task details, unresolved opinions, speculation, sensitive material, or project-local facts that belong in project docs.

4. Preserve useful heuristics.
   - Convert absolute corrections into conditional rules when exceptions are likely.
   - Keep the trigger specific enough to avoid overfiring.
   - Add negative examples when a rule could be misapplied.
   - Remove or narrow old guidance that conflicts with the correction instead of stacking new prose on top.

5. Handle false assumptions explicitly.
   - State the assumption that failed.
   - Add the verification step that would have caught it.
   - Prefer source inspection, examples, tests, validators, or user-confirmed context over confidence language.
   - Never hide the bad assumption by only documenting the corrected answer.

6. Verify the corrected behavior.
   - For code or data behavior, add or run a focused test, fixture, validator, or replay.
   - For prompts or skills, test at least one should-trigger example and one should-not-trigger example.
   - For schema fixes, validate against a real or representative payload.
   - For workflow fixes, dry-run the changed checklist against the failure case.
   - If executable verification is not possible, document the example-based check and remaining risk.

7. Report the integration.
   - Name the feedback type.
   - Name the changed artifact.
   - State how the bad assumption or gap is now prevented.
   - State the verification performed.
   - Mention any memory update, or explicitly say none was appropriate.

## Integration Matrix

| Feedback type | Usual artifact | Verification |
| --- | --- | --- |
| `preference` | Skill, prompt, durable memory if cross-session | Positive and negative prompt examples |
| `bug` | Code, test, regression fixture, changelog if needed | Failing-before/passing-after test |
| `missing context` | Skill lookup step, docs, project notes | Re-run with the missing context available |
| `false assumption` | Verification step, source requirement, guardrail | Example that would have caught the assumption |
| `schema issue` | Schema, parser, fixture, docs | Validate representative payloads |
| `workflow gap` | Checklist, script, handoff doc | Dry-run or replay the workflow |

## Durable Memory Rules

Update durable memory only when all are true:
- correction is stable beyond the current task
- correction changes how future Codex sessions should behave
- correction is not better stored in a project-local file, skill, test, or doc
- user asked for memory or the preference is clearly recurring and low-risk

When writing memory, store the operational rule, not a transcript of the disagreement.

## Quality Bar

A good integration is specific, verified, and smaller than the mistake it prevents.

Reject changes that:
- turn one correction into a broad personality rule
- bury the correction inside generic best-practice text
- add memory when a test or fixture would be stronger
- preserve conflicting instructions without resolving priority
- skip verification because the fix seems obvious
