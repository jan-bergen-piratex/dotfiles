---
name: prompt-qa
description: "Use when inspecting prompts, outputs, eval traces, schemas, or evidence packs for prompt bugs and schema drift."
---

# Prompt QA

Audit prompts and outputs against the evidence and contract actually provided. Treat the prompt, schema, examples, logs, and source evidence as separate artifacts.

## Inputs

Prefer these artifacts when available:
- exact prompt stack: system, developer, user, tool instructions, templates, examples
- model output, failing case, eval trace, or logs
- expected schema, validator, rubric, or downstream contract
- source evidence: documents, records, citations, database rows, tool results, screenshots, or fixtures
- user corrections and prior patches

If a critical artifact is missing, say what cannot be verified and continue only with clearly scoped provisional findings.

## Workflow

1. Inventory artifacts and label their role: prompt instruction, example, output, schema, source evidence, evaluator, or user correction.
2. Extract the intended task and output contract without adding hidden assumptions.
3. Compare output claims to evidence. Mark each important claim as supported, contradicted, missing, or prompt-derived.
4. Compare output shape to schema: required fields, field names, types, enums, nullability, cardinality, ordering, and example/schema consistency.
5. Inspect the prompt for bias, leakage, overfitting, conflicts, and assumptions that steer the model toward a false answer.
6. Look for model self-talk or private-reasoning leakage in outputs and prompt wording that encourages it.
7. Check whether recent changes are true root-cause fixes or minimal patches around an earlier wrong assumption.
8. Report findings first, with evidence and concrete fixes.

## Checks

Prompt inspection:
- conflicting instructions across hierarchy levels
- vague success criteria, missing stop conditions, or undefined terms
- examples that contradict the schema or bias the answer
- wording that supplies the answer, label, causality, or interpretation before evidence is considered
- hidden dependencies on current facts, unavailable tools, unstated business rules, or user-specific context
- patches that handle one observed failure but leave the original assumption intact

Prompt bias:
- leading labels, presupposed motives, or asymmetric framing
- "find proof that" wording where neutral evaluation is required
- one-sided examples, missing counterexamples, or reward language for confirming the premise
- base-rate, selection, or survivorship assumptions hidden in the task
- labels copied from the prompt into the output without evidentiary support

Schema drift:
- old field names still present in prompt examples
- new required fields missing from examples or output
- type drift, enum drift, nullable/non-nullable drift, or changed nesting
- rubrics that describe one shape while validators enforce another
- downstream code expecting fields the prompt no longer produces

Model self-talk:
- private scratchpad, hidden deliberation, or chain-of-thought style reasoning
- meta-commentary such as "I need to..." or "the prompt wants me to..."
- speculation presented as evidence
- confidence language unsupported by source material
- prompt instructions that ask the model to reveal hidden reasoning

Evidence artifacts:
- claims, citations, dates, IDs, names, or numbers absent from evidence
- output phrasing copied from examples instead of source material
- invented quotes or paraphrases that overstate the evidence
- prompt labels reused as findings
- evidence selectively ignored because it conflicts with the prompt's assumed answer

Wrong-assumption patches:
- fix adds an exception for one failure case while preserving wrong framing elsewhere
- tests cover only the reported failure and no nearby negatives
- schema, examples, or evaluator still encode the old assumption
- new instructions are layered on top of contradictory old instructions

## Output

Lead with findings ordered by severity. For each finding include:
- `Severity`: Critical, High, Medium, or Low
- `Location`: prompt section, output excerpt, schema field, file path, line, or artifact name
- `Problem`: what is wrong
- `Evidence`: concrete source showing it
- `Fix`: smallest defensible correction, or root-cause rewrite if a patch is unsafe
- `Verification`: how to prove the fix works

If no issues are found, say so and name residual risks or missing artifacts.

## Rules

- Do not invent evidence, schemas, tool results, or hidden prompts.
- Do not rewrite the full prompt unless asked; provide targeted patches by default.
- Integrate user corrections explicitly and re-check affected findings.
- Prefer primary artifacts over summaries.
- Keep recommendations compact and testable.
