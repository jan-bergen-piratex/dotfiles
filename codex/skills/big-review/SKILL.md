---
name: big-review
description: Analyze a substantial project, workflow, deployment, or documentation set from multiple angles using subagents. Use when Jan asks for a big review, skeptical review, readiness check, or multi-angle analysis of intention, architecture, execution, runtime behavior, scalability, maintainability, or ops.
---

# Big Review

Run a broad, skeptical review of one project or workflow. Reconstruct what it is supposed to do, inspect the available artifacts, then split the analysis into independent angles so each one can challenge the others.

## Use This Skill For

- Readiness or go/no-go calls on a substantial project
- "Is this good enough?" reviews
- Multi-angle analysis of a workflow, deployment, or system
- Comparing intention against architecture, execution, runtime behavior, scale, and maintainability
- Reviews where one opinion is not enough and independent skepticism matters

Do not use this skill for a narrow code review, a tiny fix, or casual brainstorming.

## Workflow

1. Frame the decision.
   - What is being reviewed?
   - What does "good enough" mean here?
   - What would be a credible failure mode?

2. Reconstruct requirements from artifacts.
   - Use docs, prompts, handovers, repo state, runtime evidence, and logs.
   - Treat chat claims and prior agent summaries as weak until checked.
   - Mark inferred requirements as inferred.

3. Split the review into angles.
   - Use `subagent-spawner` and spawn one subagent per angle when the review is broad enough.
   - Default angles: intention / requirements, architecture, execution / correctness, runtime / efficiency, scalability / maintainability, and ops / handoff.
   - Add or remove angles based on the project.
   - If Jan explicitly asks for Spark, use Spark-backed subagents when the current Codex surface supports per-agent model selection. If Spark cannot be selected for subagents in the current surface, say so plainly and continue with the closest supported setup.

4. Keep each subagent bounded.
   - Give each one a single responsibility.
   - Ask for evidence, contrary cases, and concrete risks.
   - Use read-only work unless Jan explicitly wants edits.
   - When Spark is requested, make the subagent prompt say that it is a Spark review worker and should stay concise and fast unless the task needs deeper reasoning.

5. Merge the results in the main thread.
   - Compare the angles against each other.
   - Call out contradictions, unproven claims, stale assumptions, and hidden coupling.
   - Prefer a small number of concrete fixes over a long observation dump.

## Output Shape

Choose the smallest useful shape:

- Verdict when the question is readiness or "should we keep going?"
- Table when comparing angles, evidence, and status
- Ranked fix list when the review should produce follow-up work
- Short memo when the main value is the skeptical conclusion

Prefer this default layout when useful:

| Angle | Verdict | Evidence | Main risk |
|---|---|---|---|

Then add a short verdict and a ranked fix list only if they help the decision.

## Review Discipline

- Start skeptical.
- Test the contrary case.
- Do not let one good-looking subresult wash out the weak parts.
- Do not force a template when another format is clearer.
- Do not edit the reviewed project unless Jan explicitly asks for edits.
- If the review is shallow, say so.

## Notes

- This skill is for repeated big-review work, not a one-off opinion.
- Use `project-reviewer` for the review standards and `subagent-spawner` for the parallel breakdown.
- Spark is an optional execution mode for the subagents, not a new review style. Use it only when Jan names Spark or clearly wants faster/lighter workers.
