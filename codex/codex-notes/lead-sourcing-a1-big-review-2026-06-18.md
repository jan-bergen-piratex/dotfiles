# Lead Sourcing A1 Big Review Brief - 2026-06-18

## Review Question

Jan wants a skeptical review of the Lead Sourcing / A1 problem:

- Is dynamic website source understanding the core problem of A1 / Lead Finding?
- Did Manuel/Mary docs already cover this enough conceptually?
- What should OnDeck implement next so sourcing works across arbitrary source websites, not only ISM?
- Where is Jan's intuition wrong or under-specified?

## Current Mental Model To Test

Pipeline:

```text
Sourcing / Lead Finding -> Lead Enrichment -> Outreach
```

OnDeck owns campaign/step/worker state, review UI, gateway, outbox.
Mary owns Slack intake, route selection, prompt/schema/payload design.
Worker AI executes bounded OnDeck tasks and returns typed results.

A1 / Sourcing should turn an ICP plus source scope into candidate leads for review.
A2 / Enrichment should resolve approved candidates into richer contact records.
A3 / Outreach should draft/send-gated messages from approved enriched contacts.

## Concrete Failure Trigger

Production ISM sourcing campaign used:

- source URL: `https://www.ism-cologne.de/ism-cologne-aussteller/ausstellerverzeichnis/`
- sourceMode: `public_web`
- cap/target: 150
- OpenCode worker with Serper-backed source evidence

Worker failed with:

```text
sourcing result.items must include at least one item
```

Observed likely reason: current worker uses Serper snippets and prompt, not a real source profiler/extractor. ISM page is a paginated event directory with server-rendered results and pagination. Dynamic pages, JS apps, carousels, logo grids, image alt tags, and hidden APIs are expected future source shapes.

## Relevant Files

OnDeck:

- `/home/jan/pirate/ondeck/docs/lead-pipeline.md`
- `/home/jan/pirate/ondeck/docs/sourcing-enrichment-build-plan.md`
- `/home/jan/pirate/ondeck/docs/handovers/260616_lead_pipeline_manuel_handover.md`
- `/home/jan/pirate/ondeck/backend/lead_worker.py`
- `/home/jan/pirate/ondeck/backend/lead_worker_opencode.py`
- `/home/jan/pirate/ondeck/backend/server.py`
- `/home/jan/pirate/ondeck/src/archetypes/LeadPipelineView.tsx`
- `/home/jan/pirate/ondeck/runtime/db/migrations/0022_lead_pipeline.sql`

Mary / Manuel A1:

- `/home/jan/pirate/tech-brain/mary/workflows/lead-sourcing/START_HERE.md`
- `/home/jan/pirate/tech-brain/mary/workflows/lead-sourcing/workflow-blueprint.md`
- `/home/jan/pirate/tech-brain/mary/workflows/lead-sourcing/skills/source-profiler/skill-spec.md`
- `/home/jan/pirate/tech-brain/mary/workflows/lead-sourcing/skills/candidate-route-composer/skill-spec.md`
- `/home/jan/pirate/tech-brain/mary/skills/live/mary-lead-enrichment/SKILL.md`
- `/home/jan/pirate/tech-brain/mary/skills/live/mary-lead-enrichment/references/dmexco-omclub-source-profile.md`
- `/home/jan/pirate/tech-brain/mary/skills/live/automation/website-scraping-and-monitoring/SKILL.md`
- `/home/jan/pirate/tech-brain/mary/skills/live/automation/website-scraping-and-monitoring/references/carousel-exhibitor-directory.md`
- `/home/jan/pirate/tech-brain/mary/skills/live/automation/website-scraping-and-monitoring/references/dmexco-exhibitor-guide.md`

Legacy/historical useful:

- `/home/jan/pirate/tech-brain/todos-jan/lead-enrichment/toolkit/web/event_list_extractor.py`
- `/home/jan/pirate/tech-brain/todos-jan/lead-enrichment/toolkit/tests/test_golden_fixtures.py`
- `/home/jan/pirate/tech-brain/todos-jan/lead-enrichment/toolkit/tests/fixtures/golden_event_page.html`
- `/home/jan/pirate/piratex-brain/60_Prozesse/01_Marketing-Leadgenerierung/lead-generation-enrichment/known-patterns/source-patterns/website-or-event-page.md`
- `/home/jan/pirate/piratex-brain/60_Prozesse/01_Marketing-Leadgenerierung/lead-generation-enrichment/known-patterns/source-patterns/structured-bulk-source.md`

## Review Constraints

- Read-only review. Do not edit repo files.
- Treat chat summaries as weak. Use files/code evidence.
- Be critical. Test contrary case: maybe arbitrary dynamic website scraping is too broad for A1 v1, or should be an external extractor service, or should force upload/manual source records.
- Focus on next architectural decision, not UI polish.
- Output concise findings with evidence and concrete risks.

