# OnDeck Lead Sourcing Big Review Brief - 2026-06-18

## Review Question

Jan is concerned that the current implementation path may be too shallow for the central A1/lead-sourcing problem: given an arbitrary source website, find the useful lead inventory dynamically enough to support future campaigns.

Current proposed architecture:

```text
Source URL -> Source Profile -> Extraction Plan -> deterministic/browser tool execution -> Source Records -> LLM screening/normalization -> Candidate WorkItems
```

The review should be skeptical. Do not assume the current implementation is right because it exists. Treat chat summaries and prior agent claims as weak evidence until checked against files.

## Important Context

- OnDeck is the orchestrator and UI/review system.
- Mary gathers campaign requirements from Slack and should produce an OnDeck API call.
- A background worker/scrubber does bounded AI work for sourcing, enrichment, and outreach.
- The user wants one lead-generation worker queue across sourcing/enrichment/outreach jobs.
- Sourcing must handle sources like ISM exhibitor directories, DMEXCO pages, logo grids, paginated lists, JS-rendered directories, and noisy/blocked sources.
- The user asked whether this is the central problem Manuel had already thought about under A1.
- Current branch: `lead-pipeline-walking-skeleton`.
- Current OnDeck changes are uncommitted and include `backend/lead_source_ingestion.py`, migration `0025`, tests/fixtures, and docs updates.

## Critical Risks To Test

1. Are we solving source discovery/extraction generally enough, or just patching ISM-shaped pages?
2. Should the LLM decide extraction strategy before deterministic/browser tools execute?
3. What exactly did Mary/A1 docs already require that the OnDeck implementation is missing?
4. Is the current OpenCode/Serper design respecting OnDeck boundaries, connector caps, and reviewability?
5. What would fail first on a real event directory with JS, pagination, hidden APIs, anti-bot behavior, or logo-only pages?

## Relevant Files

- `/home/jan/pirate/ondeck/docs/lead-pipeline.md`
- `/home/jan/pirate/ondeck/docs/lead-pipeline-foundry-review.md`
- `/home/jan/pirate/ondeck/docs/lead-pipeline-wayfinder-review.md`
- `/home/jan/pirate/ondeck/docs/handovers/260616_lead_pipeline_manuel_handover.md`
- `/home/jan/pirate/ondeck/backend/lead_source_ingestion.py`
- `/home/jan/pirate/ondeck/backend/lead_worker.py`
- `/home/jan/pirate/ondeck/backend/lead_worker_opencode.py`
- `/home/jan/pirate/ondeck/backend/server.py`
- `/home/jan/pirate/ondeck/backend/API.md`
- `/home/jan/pirate/ondeck/backend/tests/test_lead_worker.py`
- `/home/jan/pirate/ondeck/backend/tests/fixtures/source_ingestion/`
- `/home/jan/pirate/ondeck/runtime/db/migrations/0025_lead_source_ingestion.sql`
- `/home/jan/pirate/tech-brain/mary/workflows/lead-sourcing/START_HERE.md`
- `/home/jan/pirate/tech-brain/mary/workflows/lead-sourcing/assistant.md`
- `/home/jan/pirate/tech-brain/mary/workflows/lead-sourcing/workflow-blueprint.md`
- `/home/jan/pirate/tech-brain/mary/workflows/lead-sourcing/skills/source-profiler/skill-spec.md`
- `/home/jan/pirate/tech-brain/mary/runtime/ondeck-handoff.md`
- `/home/jan/pirate/tech-brain/_archive/assistants-lead-to-outreach-PLAN.md` as historical A1/A2/A3/A4 context only.

## Output Desired

Return a critical, evidence-based review. Include:

- verdict on the current direction;
- highest-risk gaps;
- what to change before continuing implementation;
- what can wait;
- specific file/path evidence.

Do not edit files.
