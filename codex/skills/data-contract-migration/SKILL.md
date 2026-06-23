---
name: data-contract-migration
description: "Use when pipeline schemas, JSONL/CSV rows, checkpoints, or derived artifacts may be stale or incompatible."
---

# Data Contract Migration

## Stance

Treat existing artifacts as evidence, not trash. Never silently mix stale JSONL/schema rows with current rows. If provenance or schema compatibility is uncertain, fail closed: stop append/resume and present concrete evidence.

Keep changes narrow. Preserve raw inputs, old outputs, sidecars, logs, commands, and sample stale rows until cleanup is explicitly approved.

## Contract Discovery

1. Identify the current expected contract from the strongest local source:
   - writer/serializer code, typed structs/models, migrations, JSON Schema, SQL DDL, CSV headers, fixtures, or tests
   - required/optional fields, types, nullability, defaults, enums, IDs, timestamps, order requirements, and schema/version fields
   - expected sidecars: schema hash, manifest format, checkpoint fields, run args, commit, and row counts
2. Inventory existing artifacts before writing:
   - output files, partial/temp files, schemas, manifests, checkpoints, logs, caches, indexes, and downstream summaries
   - raw input paths needed for rebuild or verification
   - modification times, sizes, row counts, and embedded contract/version markers
3. Prefer structured parsers. Use grep only for triage; confirm JSONL/CSV/schema facts with parsers or repo-native code.

## Stale Artifact Detection

Flag an artifact as stale or unsafe when any of these hold:
- any row misses a current required field, has a retired field that changes meaning, or has incompatible type/nullability
- one target file contains mixed key sets, mixed schema versions, or mixed sidecar hashes
- output schema differs from the current writer/schema contract
- checkpoint/resume state references old fields, old command args, old source paths, old contract hash, or unknown provenance
- artifact counts disagree with manifests or downstream summaries
- artifact was produced before a relevant code/schema change and lacks a reliable contract marker
- partial output exists, temp files remain, or an interrupted run cannot be proven compatible

If only some rows are stale, treat the whole append target as unsafe until it is migrated or rebuilt.

## Append/Resume Gate

Before append/resume, answer:
- What is the current expected contract?
- Which existing files will be read from or appended to?
- Do all existing rows and sidecars match the current contract?
- Is the resume checkpoint compatible with the current command, inputs, and writer?
- Are raw inputs and old outputs preserved so recovery is possible?

Block append/resume if any answer is missing or negative. Do not use force, skip-existing, or resume flags to bypass a stale-contract finding unless the user explicitly accepts a migration/recovery plan.

## Migration/Recovery Plan

Write a concise plan before changing artifacts:
- current contract source and old/stale contract evidence
- affected files with paths, sizes/counts, and representative stale row/schema samples
- raw input and evidence paths that must be preserved
- chosen recovery path: fresh rebuild, lossless row migration, or quarantine and restart
- exact destination paths for rebuilt/migrated artifacts
- rollback path and cleanup deferred until verification passes

Prefer writing rebuilt/migrated data to a new directory or temp path, then atomically replace only after verification. Do not edit old JSONL in place when stale rows may be mixed with current rows.

## Evidence Preservation

Keep enough evidence for another agent or user to audit the decision:
- raw input paths and source hashes/counts where practical
- old artifact paths, schema/manifest/checkpoint paths, sizes, mtimes, and counts
- sample stale rows with file path and line number or offset when possible
- commands used to inspect, migrate, rebuild, and verify
- skipped/error row files and reasons

Do not delete or overwrite evidence during migration. If disk pressure requires cleanup, ask first and name what will be removed.

## Verification Before Continuing

Continue the pipeline only after verification passes:
- parse every rebuilt/migrated JSONL/CSV row when feasible
- compare output row counts to raw input counts, manifest counts, and downstream summary counts
- verify no row contains stale key sets, old `schema_version` values, or mismatched contract hashes
- sample first, middle, last, and migrated rows
- validate sidecars/checkpoints/manifests against the new contract
- run focused tests or the repo verification command when available

If verification fails, stop. Preserve failed outputs and write recovery instructions instead of resuming.

## Final Report

Report whether append/resume was allowed or blocked, current contract source, affected artifact paths, migration/rebuild action taken or recommended, preserved evidence paths, verification commands/results, counts, sample-row checks, and remaining risks.
