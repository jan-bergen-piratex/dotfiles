# OnDeck Prod Lead Worker Plan

Status file for Jan/Codex coordination. Local note, not repo source of truth.

Current known state:
- OnDeck prod app is deployed at commit `4f41ba9`.
- Prod health passes.
- Prod lead pipeline endpoints pass read-only smoke.
- Prod Serper readiness passes with daily cap `60`.
- Prod enrichment archive readiness passes: `/data/enrichment.sqlite`, 20568 people, 19009 connections.
- Prod lead worker mode is `http`, pointing at `ondeck-lead-worker-opencode`.
- The queued-task redispatch bug is fixed in code and deployed.
- Public-web sourcing now fetches Serper evidence through OnDeck gateway before OpenCode prompt construction.
- OpenCode candidate labels now normalize object-shaped `person`/`company` fields into plain strings.

Goal:
- Make a prod campaign sourcing job move through `queued -> running -> succeeded/failed` with a real worker path, without enabling live sending.

Checklist:
1. [done] Prove whether prod image/container can run the lead worker and whether OpenCode is available there.
   - Result: current prod container has Python app code, but no `opencode`, no `node`, no `npm`.
   - Result: only OnDeck server container is running for Coolify project `ondeck`; no lead-worker container exists.
   - Consequence: real OpenCode worker cannot run in current prod image.
2. [done] Pick first test mode:
   - preferred: `http` worker with `ONDECK_LEAD_WORKER_IMPL=opencode`;
   - fallback: `http` worker with `dry-run` only to prove queue plumbing;
   - avoid: leaving prod in `manual` for campaign tests.
   - Decision: use `http` worker with `opencode` against live `https://ondeck.internals.pirate.builders`.
3. [done] Configure prod worker service:
   - run `lead-worker` container/service;
   - set `ONDECK_LEAD_WORKER_MODE=http` on OnDeck app;
   - set `ONDECK_LEAD_WORKER_URL` to worker service URL;
   - set worker impl/env;
   - decide `ONDECK_LEAD_WORKER_AUTO_DISPATCH`.
   - Result: temporary sidecar `ondeck-lead-worker-opencode` is running on VPS Docker network `coolify` with a worker-specific `/health` check.
   - Result: worker image is OnDeck commit `1b0e78f`; worker runs as UID/GID `10000`, using Mary OpenCode runtime mounted at `/opt/data`.
   - Result: `ONDECK_LEAD_WORKER_MODE=http`, `ONDECK_LEAD_WORKER_URL=http://ondeck-lead-worker-opencode:8094/worker/lead-pipeline`, `ONDECK_LEAD_WORKER_TIMEOUT_SECONDS=120`, `ONDECK_LEAD_WORKER_AUTO_DISPATCH=true`.
   - Result: `ONDECK_PUBLIC_BASE_URL=https://ondeck.internals.pirate.builders`.
   - Caveat: sidecar is operationally configured with `docker run`, not yet durable Coolify/IaC.
4. [done] Run prod read-only readiness smoke.
   - Result: strict lead-pipeline deploy smoke passes.
5. [done] Run one tiny write smoke:
   - create a small test campaign;
   - start sourcing;
   - verify worker task status changes;
   - verify created review card.
   - Result: campaign `codex-opencode-smoke-20260618c` created live.
   - Result: task `worker-task-4f9315f1a1a2` ran through OpenCode and succeeded.
   - Result: review card `sourcing-worker-task-4f9315f1a1a2` created.
   - Result: two pre-fix smoke tasks were cancelled after cleanup.
   - Finding: current OpenCode worker can process provided source evidence, but does not itself fetch/browse a source URL.
6. [done] If OpenCode missing in prod image, choose install route:
   - Decision for now: run external sidecar worker with Mary OpenCode runtime mounted at `/opt/data`.
   - Durable follow-up remains: convert sidecar into Coolify/IaC-managed worker or bake OpenCode into a worker image.
7. [done] Implement public-web source evidence path for OpenCode sourcing:
   - Result: OpenCode sourcing now calls OnDeck gateway `web.search` for public-web/source-URL tasks when no `sourceEvidence` is already present.
   - Result: Serper organic results are attached as `sourceEvidence` before prompt construction.
   - Result: task fails with `public-web sourcing found no source evidence` if the search returns no usable evidence.
   - Local verification: focused OpenCode worker tests pass; full `npm test` passes; `npm run build` passes with existing warnings.
8. [done] Deploy and live-test public-web sourcing:
   - Result: commit `800edc5` deployed first for public-web evidence.
   - Result: commit `001b6e5` deployed after fixing object-shaped OpenCode labels.
   - Result: commit `4f41ba9` deployed after recording the sidecar token requirement learning.
   - Result: strict prod smoke passes on live with `lead_worker`, callback URL, Serper, and enrichment archive all `pass`.
   - Result: live public-web smoke `codex-public-web-smoke-20260618b` succeeded with task `worker-task-9697dd94a3a7` and review card `sourcing-worker-task-9697dd94a3a7`.
   - Result: candidate fields are plain strings; `hasJsonLabel=false`.
   - Cleanup: smoke campaigns were cancelled and smoke review cards were skipped via review decisions.
9. [pending] Clean known drift after worker proof:
   - Mary handoff schema example still old JSON-Schema style;
   - OpenCode worker prompt still mentions removed `recommended` field.

Rules:
- Do not create/approve real outreach during lead-pipeline worker tests unless Jan explicitly confirms.
- Do not store secrets in repo or this note.
- Prefer read-only checks before config changes.
- Make Jan do GUI/Coolify steps only when shell/API path is unavailable.

Next action:
- Run a real operator-created campaign from the UI and inspect whether the Sourcing prompt/source fields produce a useful candidate, not only a technically valid review card.
