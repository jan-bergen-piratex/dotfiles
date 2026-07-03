# Handover — OnDeck Generic HITL Boundary

from: current Codex session / Jan-assisted
to: incoming Codex session on Jan's new device
date: 2026-06-23
time: 11:59 Europe/Berlin

## Project

This session is about OnDeck, now scoped as a generic human-in-the-loop
observation, approval, correction, handback, and feedback tool for assistant
work.

The important conceptual correction is that OnDeck must not own assistant or
domain workflow state. It is not a lead campaign engine, sourcing engine, worker
runtime, queue runner, CRM staging engine, tender pipeline, or accounting
process manager. External assistants such as Mary/Hermes own the work loops and
push reviewable items into OnDeck.

## Verified Current Repo State

- Repo: `/home/redbeard/pirate/ondeck`
- Branch: `wip/hitl-docs-on-main`
- Remote tracking branch: `origin/wip/hitl-docs-on-main`
- Current HEAD: `ed404b7 docs: align live code with hitl boundary`
- Base visible in recent log: `origin/main` at `acb74fa Merge pull request #2 from PIRATEglobal/feat/generic-context-model-release`
- Worktree status at handoff creation: dirty, with many branch-local changes.

Important dirty changes include:

- `docs/generic-jobs-platform.md`: active canon now says OnDeck never owns domain
  workflow state.
- `backend/API.md`: Hermes/work-item API docs now say OnDeck stores review,
  approval, rejection, correction, handback, and feedback records only.
- Lead-pipeline and worker artifacts are being removed from the active product
  surface: old `lead_worker*`, lead-pipeline tests, source surfaces, campaign
  dry acceptance, and related planning docs are deleted in the worktree.
- New or modified generic surfaces exist in the worktree:
  - `src/archetypes/JobsView.tsx`
  - `src/archetypes/JobReviewQueueView.tsx`
  - `src/surfaces/triageSurface.ts`
  - `src/lib/deriveJobs.ts`
  - `src/views/TriageView.tsx`
  - `src/archetypes/CorrectForm.tsx`
  - `src/archetypes/ApproveGate.tsx`
- Scratch/current todo file in repo: `/home/redbeard/pirate/ondeck/agents-todos.md`

## Canon To Read First

Read in this order:

1. `/home/redbeard/pirate/ondeck/docs/generic-jobs-platform.md`
2. `/home/redbeard/pirate/ondeck/backend/API.md`
3. `/home/redbeard/pirate/ondeck/agents-todos.md`
4. `/home/redbeard/pirate/ondeck/src/lib/deriveJobs.ts`
5. `/home/redbeard/pirate/ondeck/src/archetypes/JobsView.tsx`
6. `/home/redbeard/pirate/ondeck/src/archetypes/JobReviewQueueView.tsx`
7. `/home/redbeard/pirate/ondeck/src/views/TriageView.tsx`

Older handovers:

- `/home/redbeard/pirate/ondeck/docs/handovers/260619_handover.md`
- `/home/redbeard/pirate/ondeck/docs/handovers/260620_handover.md`

Those older handovers document the security/generic-context branch history, but
some lead-pipeline assumptions in old docs are superseded by the current HITL
boundary.

## Key Decisions

- OnDeck is a generic HITL product.
- OnDeck stores Jobs as context/grouping only.
- `jobId` is the grouping key external assistants send through the API.
- OnDeck stores WorkItems and ReviewDecisions.
- OnDeck stores approvals, rejects, corrections, handbacks, feedback exports,
  and activity/audit trails.
- OnDeck may store source rows/files/artifacts as review context, but that does
  not make it owner of the process.
- OnDeck must not own sourcing, enrichment, outreach, tender research,
  accounting, retries, batching, scheduling, or worker execution.
- External assistants own loops. Jan has chosen Manuel's thesis for now: rely on
  the assistant/harness to perform large loops, even large prompt-based loops.
- Mary/Hermes should push WorkItems into OnDeck and later read decision exports
  or handbacks to continue.
- Serper is a read connector exposed through OnDeck gateway for audited/capped
  access. It is a Mary/Hermes tool, not an OnDeck sourcing runtime.

## Important Mental Model

Target flow:

```text
external assistant produces reviewable output
  -> sends WorkItems to OnDeck with jobId/jobLabel
  -> human reviews in Deck
  -> OnDeck stores decisions/corrections/handbacks
  -> external assistant reads decisions
  -> external assistant continues outside OnDeck
```

OnDeck is allowed to answer:

- What needs human review?
- What did the human approve/reject/correct/hand back?
- Which WorkItems belong to the same Job context?
- What feedback should the assistant learn from?

OnDeck is not allowed to answer as owner:

- What is the next workflow step?
- Which row should be processed next?
- How should sourcing/enrichment/outreach be looped?
- Which retry policy applies?
- Whether a domain process is complete beyond the status implied by review
  records.

## Current Implementation Direction

The active direction is closer to a clean generic HITL core than to the earlier
lead-pipeline UI. Jan asked whether a full reimplementation would be easiest.
The answer given was: likely yes for the generic HITL core, while salvaging
auth/session, SQLite/migration style, WorkItem/decision concepts, outbox approve
gate pieces, connector read gateway, visual Deck components, and matching tests.

This does not necessarily mean deleting the repo. It means resisting further
patching around lead-pipeline remnants if a simple generic core is cleaner.

## Recent UI / Product Discussions

Jan's latest unresolved thought before this handoff:

> the main thing I see now for OnDeck is that the different types of data to
> approve look different: mails, ...

Interpretation: the next product problem is probably renderer architecture.
Different WorkItem archetypes need different review UIs, while sharing the same
Deck/Job/Decision/Feedback primitives. Do not solve this by creating a process
engine. Solve it as type-specific review renderers over generic HITL records.

Current useful archetype direction:

- `approve_gate`: message/mail/LinkedIn approval.
- `correct_form`: structured data correction.
- `triage_queue`: keep/skip/triage.
- `status`: domain/status review cards such as tender, analytics, social,
  website/PR status.
- `inbox`, `activity`, `outbox`: existing/legacy surfaces that may still matter.

## Handback / Resume Model

Documented in `/home/redbeard/pirate/ondeck/backend/API.md`.

Base resume flow:

1. Mary/Hermes ingests WorkItems under one `jobId`.
2. Human reviews in OnDeck.
3. Human tells Mary in Slack: `continue job <jobId>`.
4. Mary/Hermes reads the job decision export.
5. Mary/Hermes drafts next-step WorkItems for accepted/corrected rows, repairs
   handbacks, ignores terminal rejects/skips, and reports still-pending rows.

Optional future hook:

- OnDeck may notify Hermes when review is complete, but that reactivates Mary.
  It does not make OnDeck own the next AI loop.

## Local Test Context

The repo contains a local smoke prompt:

- `/home/redbeard/pirate/ondeck/docs/local-real-lead-review-smoke-prompt.md`

It was created for a realistic local Mary/Hermes-style test using Manuel Koelman
and Jan Bergen. It posts enrichment review items, waits for human review, then
resumes into outreach approval drafts without sending.

There was also local test-data cleanup:

- Old local campaigns were deleted from `/home/redbeard/pirate/ondeck/data/ondeck.sqlite`.
- Backup mentioned in `agents-todos.md`:
  `/home/redbeard/pirate/ondeck/data/ondeck.sqlite.before-delete-campaigns-20260622-115346.bak`

## Credentials / Runtime Notes

- Production OnDeck target documented in API docs:
  `https://ondeck.internals.pirate.builders`
- Hermes same-network target:
  `http://ondeck:8080`
- Secret values must not be included in docs.
- Serper key was said by Manuel to be in Coolify and Bitwarden.
- `ONDECK_HERMES_TOKEN` is the capability token for `/api/hermes/*`.
- Browser/session auth uses `ONDECK_API_TOKEN` through `POST /api/session`.
- OnDeck gateway read is the right place for audited/capped connector reads, but
  the assistant owns why and when it calls those reads.

## Open Questions

- How generic should Deck renderer configuration become?
  Current tension: emails, structured data, tender cards, accounting documents,
  and status reviews need different UI shapes.
- Should there be a clean full reimplementation of the generic core, or a
  continued cleanup of the current branch? Current opinion: full core
  reimplementation may be easiest if scoped tightly.
- Which existing code should be salvaged exactly?
  Likely: auth/session, DB migration style, WorkItem/Decision APIs, approve gate,
  gateway read, and useful UI components.
- How much of old `status`, `inbox`, `outbox`, and connector surfaces belong in
  the first generic HITL release?
- How should renderer contracts be represented: per `archetype`, per
  `payload.kind`, or explicit `renderRequest`?

## Next Actions

- Task: review current dirty branch and decide whether to finish cleanup or start
  a clean generic core branch.
  Owner: incoming Codex + Jan.
  Source: current session decision that lead-pipeline remnants are expensive.
  Next action: inspect `git status --short`, then read canon/API/agents-todos.

- Task: design renderer architecture for different approval data types.
  Owner: incoming Codex + Jan.
  Source: Jan's latest unresolved thought.
  Next action: list concrete review card families: mail approval, structured
  correction, triage, tender/status, accounting document, social draft.

- Task: verify current tests after local branch cleanup.
  Owner: incoming Codex.
  Source: dirty worktree with broad deletions.
  Next action: run targeted tests first, then full `npm run test`/`npm run build`
  if the branch is intended to continue.

- Task: keep docs and API aligned with the HITL boundary.
  Owner: incoming Codex.
  Source: recent correction from Jan.
  Next action: grep for old wording like campaign engine, lead worker, sourcing
  controls, queue dispatch, OnDeck owns state.

## Must Not Change Or Re-Litigate Without Jan/Manuel

- Do not reintroduce OnDeck-owned sourcing/enrichment/outreach state.
- Do not rebuild the lead campaign engine under a new name.
- Do not make OnDeck a generic worker runtime or queue dispatcher.
- Do not treat `Job` as lifecycle ownership. It is context/grouping only.
- Do not treat `Job Entity` or `Job Import` as source-of-truth process state.
- Do not put campaign/workflow execution state into the enrichment archive.
- Do not include secret values in handoffs or docs.
- Do not deploy, merge, or push without explicit instruction in the new session.
- Do not edit `/home/redbeard/pirate/notes/todos.md` as part of this handoff thread.

## Assumptions

- The current dirty branch reflects active local cleanup work, not a stable final
  product.
- Manuel's broader vision remains: OnDeck is generic across assistants, not a
  dedicated lead-enrichment app.
- Jan accepts that generic HITL can be tried even if the renderer/layout problem
  is still open.
- Large assistant loops live in the assistant/harness, not in OnDeck.

## Missing Evidence

- No fresh production deployment state was verified during this handoff request.
- No tests were run during this handoff request.
- The current exact runtime behavior of the dirty branch was not re-smoked.
- The latest Manuel changes after this branch, if any, were not fetched in this
  handoff request.
