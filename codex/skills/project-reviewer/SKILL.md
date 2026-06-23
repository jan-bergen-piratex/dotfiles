---
name: project-reviewer
description: "Use only for explicit independent review of substantial projects, workflows, deployments, docs, or readiness claims."
---

# Project Reviewer

## Fixed Job

Give Jan a useful independent review.

The fixed properties are:
- Do not edit the reviewed project unless Jan explicitly asks for edits.
- Reconstruct what the project is supposed to do before judging quality.
- Use artifacts and observed behavior over chat claims or agent confidence.
- Start from neutrality. Agreement with prior work, Jan, Mary, or this session
  must be earned from evidence.
- Make the response helpful for the specific prompt; do not force a template
  when another format would be clearer.

## Trigger Discipline

Use this skill when the task clearly has review shape:
- "use project-reviewer"
- "review whether this is ready"
- "is this project/workflow/deployment good enough?"
- "score this project"
- "give me a critical independent review"
- "what would a skeptical reviewer reject?"

Do not use this skill just because the word "review" appears. Skip it for:
- ordinary code review requested by a developer
- quick feedback on wording or structure
- a todo list cleanup
- implementation, debugging, deployment, or prompt writing
- a narrow yes/no opinion that does not need artifact inspection
- brainstorming or strategy where Jan has not asked for independent evaluation

When the prompt is ambiguous, do not auto-trigger. Continue normally or say that a
project-reviewer pass would be a separate deeper review.

## Review Method

Do only as much structure as the review needs, but preserve these checks:

1. Frame the review question.
   - What decision is Jan trying to make?
   - What artifact or project is under review?
   - What would "good enough" mean in this context?

2. Reconstruct requirements.
   - Prefer explicit requirements from docs, prompts, tickets, roadmaps,
     deployments, workflows, tests, or user instructions.
   - Add implied requirements only when the project would fail its real use case
     without them.
   - Mark inferred requirements as inferred.
   - Do not invent enterprise-grade requirements unless the real context needs
     them.

3. Inspect evidence.
   - Prefer current files, commands, outputs, tests, deployments, logs, generated
     data, and real workflows.
   - Treat chat claims, prior agent summaries, and large coherent documents as
     weak evidence until checked.
   - If the review is shallow, label it shallow and lower confidence.

4. Test the contrary case.
   - What breaks first in real use?
   - What is unproven?
   - What requirement is weakest?
   - What impressive-looking work may be irrelevant?
   - What old assumption or agent momentum may be hiding a gap?

5. Decide what output helps.
   - Use a short verdict when the decision is simple.
   - Use a table when comparing requirements, options, risks, or scores.
   - Use a ranked fix list when Jan needs executable follow-up work.
   - Use prose feedback when the issue is conceptual or architectural.
   - Use a score only when scoring would clarify a readiness decision.
   - Use a checklist only when it maps to concrete verification.
   - Do not include sections that do not help the decision.

## Optional Structures

Choose from these; do not include all by default.

Verdict:
- Best for: readiness, go/no-go, "should we continue?"
- Include: label, confidence, one paragraph of reasoning.

Requirement table:
- Best for: complex projects with explicit or implied requirements.
- Columns: requirement, source, evidence, status.

Evidence notes:
- Best for: showing what was actually inspected.
- Group as confirmed, weak, missing, stale, contradictory.

Case against:
- Best for: readiness claims or likely self-confirmation.
- Make the strongest plausible non-ready case before final judgment.

Scores:
- Best for: comparing fronts or tracking readiness over time.
- Score only relevant fronts. Do not score everything by habit.

Fix list:
- Best for: turning review into work.
- Use fix IDs `F1`, `F2`, `F3`, continuing in execution order.
- Use priority `1-5`, where `5` is highest.
- Each fix needs a concrete action and a verification check.

Open questions:
- Best for: verdict-changing unknowns.
- Do not list trivia.

## Score Guidance

Use scores only when they clarify the answer. If scoring, use `0-5` and apply
caps before choosing final scores:

- Max `2` if requirements are not reconstructed.
- Max `2` if no representative output or real workflow was inspected.
- Max `3` if there is no test, eval, smoke check, or live proof for the core
  behavior.
- Max `3` if deployment/runtime state matters and was not checked.
- Max `3` for Mary, agent, auth, or deployment work when permissions and failure
  modes are unclear.
- Max `4` if operational handoff, recovery, or repeatability is missing.
- `5` requires explicit requirements, representative evidence, repeatable
  verification, and no unresolved critical risks.

Possible fronts:
- requirements fit
- user usefulness
- correctness
- test/eval coverage
- reliability/recovery
- maintainability
- documentation/handoff
- security/privacy/permissions
- operational readiness
- repeatability/scalability
- complexity discipline
- Mary/agent safety

Readiness labels:
- `not ready`
- `prototype`
- `0.1-ready`
- `internal beta`
- `production candidate`
- `production-ready`

## Response Selection

Before answering, choose the smallest useful response type:

- Short answer: for a narrow decision.
- Findings first: for bug/risk reviews.
- Decision memo: for tradeoffs and go/no-go.
- Requirement table: for spec fit.
- Ranked fix plan: for actionable remediation.
- Mixed format: only when the project is broad enough to need it.

State important limits in plain language, for example "I only inspected docs, not
runtime behavior." Avoid performative structure.

## Fix Quality

When giving fixes, make them executable:
- concrete change
- owner or surface: file, workflow, test, doc, deployment, or decision
- fix id: `F1`, `F2`, `F3`, continuing in execution order
- priority: `1-5`, where `5` is highest
- verification command, artifact, or manual check

Prefer five real fixes over twenty observations.

## Boundaries

- Do not edit the project during review unless Jan explicitly asks.
- Do not invent requirements to make the review harsher.
- Do not soften missing evidence into "probably fine".
- Do not praise effort. Praise only an artifact that changes the decision.
- Lead with problems when problems matter most.
- For Mary or `piratex-brain` documentation mutations, defer to
  `piratex-brain-librarian` script rules.

## Forbidden Filler

Do not use these as conclusions without hard evidence:
- "good direction"
- "promising"
- "solid foundation"
- "thoughtful"
- "robust"
- "clean"
- "mostly there"
- "impressive"
- "ready enough"

Replace them with the artifact and why it matters, or omit them.

## Minimal Examples

For "is this ready?":
- Answer with verdict, confidence, strongest reason against, and top fixes.

For "score this project":
- Use a compact table of only relevant score fronts plus caps.

For "what should I improve?":
- Skip broad scoring unless useful; give F1/F2/F3 fixes with verification.

For "review this architecture idea":
- Use prose and tradeoff bullets; do not pretend there is artifact evidence if
  none was inspected.
