# Spark Starter Prompt: GIC Missing Attachment Recovery

Use in new Codex CLI session from `/home/redbeard/pirate/gic-nextjs`.

```text
We are implementing a narrow missing-attachment recovery flow for GIC Next.js.

First, read:
- /home/redbeard/.codex/SOUL.md
- AGENTS.md
- docs/handovers/260617_missing_attachment_recovery_agent_handoff.md

Task:
Implement the first production-useful startup account recovery flow:
- use existing startup magic-link/session flow;
- add a narrow authenticated page for missing attachment uploads only;
- show only missing pitch deck / screenshots for the signed-in startup's linked application;
- upload files through existing Payload media collection;
- update only startup-applications.pitchDeck and startup-applications.screenshots;
- do not implement broad lookbook/profile editing;
- do not send live recovery emails;
- do not mutate production DB;
- do not reintroduce GitHub Actions CD;
- no secrets in code/docs/logs.

Before edits:
1. git pull --ff-only
2. inspect files listed in docs/handovers/260617_missing_attachment_recovery_agent_handoff.md
3. inspect package.json test scripts
4. give Jan a concise implementation plan

Implementation preference:
- add static non-secret recovery manifest derived from .tmp/gic-db-analysis-20260616/action_needed.csv;
- include only real startup rows with missing attachments;
- exclude placeholder duplicate Digital Realm row and unsure demo-looking rows unless Jan later approves;
- route can be /company/missing-files;
- redirect startup magic-link verify to /company/missing-files if that is the current natural startup destination, otherwise link from existing founder portal.

Acceptance:
- auth required;
- account can only update own linked startup application;
- wrong MIME/oversize rejected;
- media rows created with correct assetType and ownerEmail;
- application relationships updated;
- cleanup on partial failure;
- tests for missing-slot detection, auth guard, upload action;
- targeted tests + typecheck + lint pass, or report exact failures.
```

