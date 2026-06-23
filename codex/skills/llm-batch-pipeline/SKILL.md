---
name: llm-batch-pipeline
description: "Use for LLM batch pipelines with JSONL state, retries, logs, strict schemas, resumes, and timeout triage."
---

# LLM Batch Pipeline

## Stance

Treat the LLM as an unreliable worker behind durable state and a strict parser. The job is not done because calls finished; it is done when disk state validates.

## State Model

Use JSONL for durable, inspectable state. Each row must have enough identity and schema information to be interpreted later:

```json
{"record_id":"...","input_hash":"...","schema_version":"v1","attempt":1,"status":"succeeded","created_at":"..."}
```

Prefer explicit fields over line order:
- `record_id`: stable unique input key
- `input_hash`: detects changed inputs during resume
- `schema_version`: output/parser contract version
- `attempt`: per-record attempt number
- `status`: one of the pipeline's explicit states
- `raw_request`, `raw_response`, `provider_metadata`, `error`: enough raw evidence to audit failures

Keep append-only attempt logs when possible. If maintaining a current-state file, rebuild it deterministically from the attempt log or write it atomically through a temp file and rename.

## Required Workflow

1. Inspect existing conventions before editing.
   Find input files, output/state JSONL, raw logs, schema structs, resume code, retry code, and completion checks.

2. Define the record contract.
   Identify the stable key, expected input count, output schema version, terminal statuses, retryable statuses, and exact parser/schema used for LLM output.

3. Guard against stale appends.
   Never append to an existing JSONL file until existing rows parse under the current schema and match the current `schema_version`, required keys, record identity, and input hash policy. If schema compatibility is uncertain, write a new output directory/file or implement an explicit migration.

4. Make resume deterministic.
   On startup, read state from disk, parse it strictly, group by `record_id`, select the latest valid attempt, and enqueue only records that are missing or retryable. Re-running the same command must not duplicate successful records unless the user explicitly asks for a fresh run.

5. Preserve every failure.
   On provider errors, timeouts, validation errors, or parser failures, write a fallback row with `status`, `error`, attempt metadata, and raw request/response when available. Do not synthesize successful business fields from malformed output.

6. Retry narrowly.
   Retry transient provider/network errors, timeouts after triage, and parse failures only when another attempt can plausibly produce a valid row. Cap attempts, log each attempt, and keep final exhausted failures visible as terminal non-success statuses.

7. Triage timeouts before rerunning.
   A client timeout does not always mean the provider did no work. Check provider batch/job status, raw logs, and partial outputs first. Resume from confirmed state and preserve partial progress.

8. Validate before marking done.
   Re-read all produced JSONL from disk with the strict parser. Verify:
   - no malformed JSON lines, except a consciously handled truncated final line in an append log
   - all rows match the current schema contract
   - no duplicate successful rows for the same `record_id`
   - every expected input has exactly one current terminal state
   - failures are represented by explicit fallback rows
   - raw logs exist for successes and failures when the pipeline claims to keep them
   - summary counts match the files, not in-memory counters

## Repair Rules

When repairing a partial or failed run:
- Copy or preserve the original state before modifying it.
- Inspect `head`, `tail`, schema versions, status distribution, duplicate keys, and malformed lines.
- Prefer creating a clean regenerated state file from append-only logs over hand-editing rows.
- If only the final JSONL line is truncated, repair or drop it only after proving earlier lines parse and the dropped data is unrecoverable elsewhere.
- Do not hide old failed attempts; keep them in attempt logs and produce a current-state summary separately.

## Completion Report

Report the exact files used, counts by status, retry/exhaustion counts, validation command or method, and any remaining non-success records. If validation could not run, say why and do not call the batch complete.
