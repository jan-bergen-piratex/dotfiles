Objective:
- Remove schema description field from campaign schema editing UI.
- Change campaign creation buttons from "Save" / "Queue sourcing" to:
  - secondary: "Create campaign"
  - primary green: "Create campaign and start sourcing"
- Fix primary action so sourcing actually starts.

Current state:
- Lead pipeline setup lives in `src/archetypes/LeadPipelineView.tsx`.
- `createLeadWorkerTask` already supports `dispatch: true`.
- Primary bug likely: button only queues task, does not dispatch it.

Relevant files:
- `src/archetypes/LeadPipelineView.tsx`
- `runtime/contracts/core.ts` if schema description removal touches types
- `backend/server.py` only if runtime contract/validation needs sync
- `backend/tests/test_lead_pipeline.py` and `backend/tests/test_lead_worker.py` if contract names changed
- `backend/API.md` if contract docs still mention schema description or old flags

Non-goals:
- Do not touch unrelated pipeline layout or worker queue styling.
- Do not change campaign overview rows.

Validation:
- Run `npm run build`
- Run `npm test`
